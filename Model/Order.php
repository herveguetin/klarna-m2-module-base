<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Base\Model;

use Klarna\Base\Api\OrderInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * @internal
 */
class Order extends AbstractModel implements OrderInterface, IdentityInterface
{
    public const CACHE_TAG = 'klarna_core_order';

    /**
     * Get Identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritDoc
     */
    public function getKlarnaOrderId()
    {
        return $this->_getData('klarna_order_id');
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->_getData('order_id');
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        $this->setData('order_id', $orderId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getReservationId()
    {
        return $this->_getData('reservation_id');
    }

    /**
     * @inheritDoc
     */
    public function setReservationId($reservationId)
    {
        $this->setData('reservation_id', $reservationId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSessionId()
    {
        return $this->_getData('session_id');
    }

    /**
     * @inheritDoc
     */
    public function setSessionId($sessionId)
    {
        $this->setData('session_id', $sessionId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setKlarnaOrderId($orderId)
    {
        $this->setData('klarna_order_id', $orderId);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setIsAcknowledged($acknowledged)
    {
        $this->setData('is_acknowledged', $acknowledged);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIsAcknowledged()
    {
        return $this->_getData('is_acknowledged');
    }

    /**
     * @inheritDoc
     */
    public function isAcknowledged()
    {
        return (bool)$this->_getData('is_acknowledged');
    }

    /**
     * @inheritDoc
     */
    public function setUsedMid(string $mid): OrderInterface
    {
        $this->setData('used_mid', $mid);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUsedMid(): ?string
    {
        return $this->_getData('used_mid');
    }

    /**
     * @inheritDoc
     */
    public function setIsB2b(bool $flag): OrderInterface
    {
        $this->setData('is_b2b', $flag);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isB2b(): bool
    {
        return (bool) $this->_getData('is_b2b');
    }

    /**
     * Constructor
     *
     * @codeCoverageIgnore
     * @codingStandardsIgnoreLine
     */
    protected function _construct()
    {
        $this->_init(\Klarna\Base\Model\ResourceModel\Order::class);
    }
}
