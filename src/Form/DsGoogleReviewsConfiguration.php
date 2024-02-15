<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */

declare(strict_types=1);

namespace DarkSide\DsGoogleReview\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

/**
 * Configuration is used to save data to configuration table and retrieve from it
 */
final class DsGoogleReviewsConfiguration implements DataConfigurationInterface
{
    public const DS_GOOGLE_PLACE_ID = 'ds_google_place_id';
    public const DS_GOOGLE_API_KEY = 'ds_googe_api_key';
    public const DS_REVIEW_CACHE = 'ds_review_cache';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(): array
    {
        $return = [];

        if ($translatableSimple = $this->configuration->get(static::DS_GOOGLE_API_KEY)) {
            $return['dsgooglereviews_api_key'] = $translatableSimple;
        }

        if ($translatableTextArea = $this->configuration->get(static::DS_GOOGLE_PLACE_ID)) {
            $return['dsgooglereviews_place_id'] = $translatableTextArea;
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(array $configuration): array
    {
        $this->configuration->set(static::DS_GOOGLE_API_KEY, $configuration['ds_googe_api_key']);
        $this->configuration->set(static::DS_GOOGLE_PLACE_ID, $configuration['dsgooglereviews_place_id']);

        /* Errors are returned here. */
        return [];
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @param array $configuration
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        return true;
    }
}
