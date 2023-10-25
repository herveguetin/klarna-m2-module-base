<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Base\Helper;

use Klarna\Kco\Model\Payment\Kco;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Klarna\Base\Model\Api\MagentoToKlarnaLocaleMapper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @internal
 */
class ConfigHelper
{
    /**
     * @var MagentoToKlarnaLocaleMapper
     */
    private MagentoToKlarnaLocaleMapper $localeResolver;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var AddressRepositoryInterface
     */
    private AddressRepositoryInterface $addressRepository;
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param MagentoToKlarnaLocaleMapper $localeResolver
     * @param CustomerRepositoryInterface $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param ScopeConfigInterface $scopeConfig
     * @codeCoverageIgnore
     */
    public function __construct(
        MagentoToKlarnaLocaleMapper $localeResolver,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface  $addressRepository,
        ScopeConfigInterface        $scopeConfig
    ) {
        $this->localeResolver = $localeResolver;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the order status that should be set on orders that have been processed by Klarna
     *
     * @param StoreInterface $store
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getProcessedOrderStatus(StoreInterface $store, string $paymentMethod): string
    {
        return $this->getPaymentConfig('order_status', $store, $paymentMethod);
    }

    /**
     * Get payment config value
     *
     * @param string $config
     * @param StoreInterface $store
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentConfig(string $config, StoreInterface $store, string $paymentMethod): string
    {
        return (string) $this->scopeConfig->getValue(
            sprintf('payment/' . $paymentMethod . '/%s', $config),
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Get API config value
     *
     * @param string $config
     * @param StoreInterface $store
     *
     * @return string
     */
    public function getApiConfig(string $config, StoreInterface $store): string
    {
        return $this->scopeConfig->getValue(
            sprintf('klarna/api/%s', $config),
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Get the current locale code
     *
     * @return string
     */
    public function getLocaleCode(): string
    {
        $result = $this->localeResolver->getLocale();
        if ($result === null) {
            return '';
        }

        return $result;
    }

    /**
     * Get checkout design config value
     *
     * @param StoreInterface $store
     * @param string $paymentMethod
     *
     * @return array
     */
    public function getCheckoutDesignConfig(StoreInterface $store, string $paymentMethod): array
    {
        $designOptions = $this->scopeConfig->getValue(
            'checkout/' . $paymentMethod . '_design',
            ScopeInterface::SCOPE_STORES,
            $store
        );

        return is_array($designOptions) ? $designOptions : [];
    }

    /**
     * Get payment config value
     *
     * @param string $config
     * @param StoreInterface $store
     * @param string $paymentMethod
     *
     * @return bool
     */
    public function isPaymentConfigFlag(string $config, StoreInterface $store, string $paymentMethod): bool
    {
        return $this->scopeConfig->isSetFlag(
            sprintf('payment/' . $paymentMethod . '/%s', $config),
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Get API config value
     *
     * @param string $config
     * @param StoreInterface $store
     *
     * @return bool
     */
    public function isApiConfigFlag(string $config, StoreInterface $store): bool
    {
        return $this->scopeConfig->isSetFlag(
            sprintf('klarna/api/%s', $config),
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Check if b2b mode is enabled in setting
     *
     * @param StoreInterface $store
     * @param string $paymentMethod
     * @return bool
     */
    public function isB2bEnabled(StoreInterface $store, string $paymentMethod): bool
    {
        return $this->isCheckoutConfigFlag('enable_b2b', $store, $paymentMethod);
    }

    /**
     * Get checkout config value
     *
     * @param string $config
     * @param StoreInterface $store
     * @param string $paymentMethod
     *
     * @return bool
     */
    public function isCheckoutConfigFlag(string $config, StoreInterface $store, string $paymentMethod): bool
    {
        return $this->scopeConfig->isSetFlag(
            sprintf('checkout/%s/%s', $paymentMethod, $config),
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Check if this customer is a business customer
     *
     * @param string $customerId
     * @param StoreInterface $store
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isB2bCustomer(string $customerId, StoreInterface $store): bool
    {
        if ($customerId) {
            $businessIdValue = $this->getBusinessIdAttributeValue($customerId, $store);
            $businessNameValue = $this->getCompanyNameFromAddress($customerId);
            if (!empty($businessIdValue) || !empty($businessNameValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get organization id value
     *
     * @param string $customerId
     * @param StoreInterface $store
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBusinessIdAttributeValue(string $customerId, StoreInterface $store)
    {
        $customerObj = $this->customerRepository->getById($customerId);
        $businessIdValue = $customerObj->getCustomAttribute($this->getBusinessIdAttribute($store));
        if ($businessIdValue) {
            return $businessIdValue->getValue();
        }
        return false;
    }

    /**
     * Get the code for custom attribute for recording organization id
     *
     * @param StoreInterface $store
     * @return string
     */
    public function getBusinessIdAttribute(StoreInterface $store): string
    {
        return $this->getCheckoutConfig('business_id_attribute', $store, Kco::METHOD_CODE);
    }

    /**
     * Get checkout config value
     *
     * @param string $config
     * @param StoreInterface $store
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getCheckoutConfig(string $config, StoreInterface $store, string $paymentMethod): string
    {
        return (string) $this->scopeConfig->getValue(
            sprintf('checkout/%s/%s', $paymentMethod, $config),
            ScopeInterface::SCOPE_STORES,
            $store
        );
    }

    /**
     * Check if customer's default billing address contain company name
     *
     * @param string $customerId
     * @return bool|null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCompanyNameFromAddress(string $customerId)
    {
        $customerObj = $this->customerRepository->getById($customerId);
        $billingAddressId = $customerObj->getDefaultBilling();
        if ($billingAddressId) {
            try {
                $defaultBillingAddress = $this->addressRepository->getById($billingAddressId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }

            return $defaultBillingAddress->getCompany();
        }
        return false;
    }

    /**
     * Determine if FPT (Fixed Product Tax) is set to be included in the subtotal
     *
     * @param StoreInterface $store
     * @return string
     */
    public function getDisplayInSubtotalFpt(StoreInterface $store): string
    {
        return $this->scopeConfig->getValue('tax/weee/include_in_subtotal', ScopeInterface::SCOPE_STORES, $store);
    }

    /**
     * Checking if Fixed Product Taxes are enabled
     *
     * @param StoreInterface $store
     * @return bool
     */
    public function isFptEnabled(StoreInterface $store): bool
    {
        return $this->scopeConfig->isSetFlag('tax/weee/enable', ScopeInterface::SCOPE_STORES, $store);
    }
}
