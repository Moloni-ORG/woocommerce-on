<?php

namespace MoloniOn\Enums;

class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    public static function getForRender(): array
    {
        return [
            [
                'label' => __('Error', 'moloni-on'),
                'value' => self::ERROR
            ],
            [
                'label' => __('Informative', 'moloni-on'),
                'value' => self::INFO
            ],
            [
                'label' => __('Alert', 'moloni-on'),
                'value' => self::ALERT
            ],
            [
                'label' => __('Critical', 'moloni-on'),
                'value' => self::CRITICAL
            ]
        ];
    }

    public static function getTranslation(string $type): ?string
    {
        switch ($type) {
            case self::ERROR:
                return __('Error', 'moloni-on');
            case self::WARNING:
                return __('Warning', 'moloni-on');
            case self::INFO:
                return __('Informative', 'moloni-on');
            case self::DEBUG:
                return __('Debug', 'moloni-on');
            case self::ALERT:
                return __('Alert', 'moloni-on');
            case self::CRITICAL:
                return __('Critical', 'moloni-on');
            case self::EMERGENCY:
                return __('Emergency', 'moloni-on');
            case self::NOTICE:
                return __('Observation', 'moloni-on');
        }

        return $type;
    }

    public static function getClass(string $type): ?string
    {
        switch ($type) {
            case self::CRITICAL:
            case self::EMERGENCY:
            case self::ERROR:
                return 'chip--red';
            case self::ALERT:
            case self::WARNING:
                return 'chip--yellow';
            case self::NOTICE:
            case self::INFO:
                return 'chip--blue';
            case self::DEBUG:
                return 'chip--neutral';
        }

        return $type;
    }
}
