<?php

declare(strict_types=1);

class ilUpdateNotificationPlugin extends ilCronHookPlugin
{
    public const PLUGIN_CLASS_NAME = ilUpdateNotificationPlugin::class;
    public const PLUGIN_ID = 'updntf';
    public const PLUGIN_NAME = 'Update Notification Plugin';

    /** Instance of this class
     * @var self|null
     */
    protected static $instance = null;

    /**
     * @return ilUpdateNotificationPlugin|null
     */
    public static function getInstance(): ?ilUpdateNotificationPlugin
    {
        if (self::$instance) {
            return self::$instance;
        }

        return new ilUpdateNotificationPlugin();
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::PLUGIN_ID;
    }


    public function getCronJobInstances(): array
    {
        return [new ilUpdateNotificationJob()];
    }

    public function getCronJobInstance($a_job_id): ilUpdateNotificationJob
    {
        return new ilUpdateNotificationJob();
    }

    /**
     * @inheritDoc
     */
    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }
}
