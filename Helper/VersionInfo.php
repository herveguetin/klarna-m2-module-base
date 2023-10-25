<?php
/**
 * Copyright © Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */
declare(strict_types=1);

namespace Klarna\Base\Helper;

use Composer\InstalledVersions;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;
use Magento\Framework\Module\ResourceInterface;

/**
 * @internal
 */
class VersionInfo
{
    /**
     * @var State
     */
    private $appState;

    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var InstalledVersions
     */
    private InstalledVersions $installedVersions;

    /**
     * VersionInfo constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param State                    $appState
     * @param ResourceInterface        $resource
     * @param InstalledVersions        $installedVersions
     * @codeCoverageIgnore
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        State $appState,
        ResourceInterface $resource,
        InstalledVersions $installedVersions
    ) {
        $this->appState = $appState;
        $this->productMetadata = $productMetadata;
        $this->resource = $resource;
        $this->installedVersions = $installedVersions;
    }

    /**
     * Get module version info
     *
     * @param string $packageName
     * @return string|false
     */
    public function getVersion(string $packageName)
    {
        return $this->resource->getDataVersion($packageName);
    }

    /**
     * Gets the current MAGE_MODE setting
     *
     * @return string
     */
    public function getMageMode(): string
    {
        return $this->appState->getMode();
    }

    /**
     * Gets the current Magento version
     *
     * @return string
     */
    public function getMageVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Gets the current Magento Edition
     *
     * @return string
     */
    public function getMageEdition(): string
    {
        return $this->productMetadata->getEdition();
    }

    /**
     * Creates the module version string
     *
     * @param string $version
     * @param string $caller
     * @return string
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function getModuleVersionString(string $version, string $caller): string
    {
        return sprintf(
            "%s;Base/%s",
            $version,
            $this->getVersion('Klarna_Base')
        );
    }

    /**
     * Get Magento information
     *
     * @return string
     */
    public function getMageInfo(): string
    {
        return sprintf('Magento %s/%s %s mode', $this->getMageEdition(), $this->getMageVersion(), $this->getMageMode());
    }

    /**
     * Get m2-klarna version
     *
     * @return string
     */
    public function getM2KlarnaVersion(): string
    {
        $packageName = 'klarna/m2-klarna';
        $version = $this->getComposerPackageVersion($packageName);

        return sprintf('%s/%s', $packageName, $version);
    }

    /**
     * Return a given composer package version if it exists, empty string otherwise.
     *
     * @param string $packageName
     * @return string
     */
    public function getComposerPackageVersion(string $packageName): string
    {
        if ($this->installedVersions->isInstalled($packageName)) {
            return $this->installedVersions->getPrettyVersion($packageName);
        }

        return '';
    }
}
