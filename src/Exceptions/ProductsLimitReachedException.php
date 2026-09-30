<?php

namespace MoloniOn\Exceptions;

/**
 * A product could not be created in Moloni ON because the company's plan product limit
 * has been reached.
 *
 * Thrown up front when the company limits say the plan is full (see
 * Company::canCreateProducts), or when Moloni ON itself refuses the create. Product sync
 * (product save, export, manual create) logs it as a warning; while creating a document
 * it fails the document with this message.
 */
class ProductsLimitReachedException extends ServiceException
{
    /**
     * What Moloni ON answers a product create with once the plan's product limit is reached
     */
    private const API_ERROR = 'Number of items is over the allowed limit.';

    public function __construct(string $productLabel, array $data = [])
    {
        parent::__construct(self::buildMessage($productLabel), $data);
    }

    /**
     * Message for a product that could not be created because of the plan's product limit
     */
    public static function buildMessage(string $productLabel): string
    {
        return sprintf(
            // Translators: %1$s is the product reference or name.
            __('Could not create "%1$s" in Moloni ON: the plan\'s product limit has been reached.', 'moloni-on'),
            $productLabel
        );
    }

    /**
     * Whether a failed productCreate was refused because of the plan's product limit
     *
     * @param array $apiData The data of the APIExeption thrown by the request (url, sent, received)
     */
    public static function isApiError(array $apiData): bool
    {
        $errors = $apiData['received']['data']['productCreate']['errors'] ?? [];

        foreach ((array)$errors as $error) {
            if (($error['msg'] ?? '') === self::API_ERROR) {
                return true;
            }
        }

        return false;
    }
}
