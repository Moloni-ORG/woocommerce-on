<?php

namespace MoloniOn\Exceptions;

use MoloniOn\Context;

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

    /**
     * Up-front check used by document builders that create a product on the fly (shipping,
     * fees, etc): throws a DocumentError if the company's plan has no room left for it.
     *
     * @throws DocumentError
     */
    public static function assertCanCreate(string $productLabel): void
    {
        if (Context::company() && !Context::company()->canCreateProducts()) {
            throw new DocumentError(self::buildMessage($productLabel));
        }
    }

    /**
     * Map a failed productCreate into a DocumentError: the plan's product limit message if
     * that's why it failed, otherwise the caller's own fallback message. Both carry the
     * API exception data.
     */
    public static function wrap(APIExeption $e, string $productLabel, string $fallbackMessage): DocumentError
    {
        if (self::isApiError($e->getData())) {
            return new DocumentError(self::buildMessage($productLabel), [
                'message' => $e->getMessage(),
                'data' => $e->getData(),
            ]);
        }

        return new DocumentError($fallbackMessage, [
            'message' => $e->getMessage(),
            'data' => $e->getData(),
        ]);
    }
}
