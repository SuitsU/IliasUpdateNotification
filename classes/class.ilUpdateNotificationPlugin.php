<?php

declare(strict_types=1);
require_once('class.ilUpdateNotificationJob.php');

class ilUpdateNotificationPlugin extends ilCronHookPlugin
{
    public const PLUGIN_CLASS_NAME = ilUpdateNotificationPlugin::class;
    public const PLUGIN_ID = 'updntf';
    public const PLUGIN_NAME = 'UpdateNotification';
    #const DEFAULT_ROLES_ADMINISTRATE_CERTIFICATES = '["2"]'; (aus anderem Plugin)
    public const ADMIN_ROLE_IDS = '["2"]';
    public const ADMIN_ROLE_ID = 2;

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

    /**
     * @return ilUpdateNotificationJob[]
     */
    public function getCronJobInstances(): array
    {
        return [new ilUpdateNotificationJob()];
    }

    /**
     * @param $a_job_id
     * @return ilUpdateNotificationJob
     */
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

    /** Delete all settings when uninstalled
     * @return bool
     */
    protected function beforeUninstall() : bool
    {
        try {

            $settings = new ilSetting(self::PLUGIN_ID);
            $notification_id = intval($settings->get('notification_id','-1'));
            if($notification_id > 0) {
                $il_adn_notification = new ilADNNotification($notification_id);
                $il_adn_notification->delete();
            }

            // $settings->deleteAll();

            $settings->delete('insistence');
            $settings->delete('user_groups');
            $settings->delete('email_recipients');
            $settings->delete('update_url');
            $settings->delete('notification_id');
            $settings->delete('minor_notification_id');
            $settings->delete('major_notification_id');
            $settings->delete('newest_minor_version');
            $settings->delete('next_major_version');
            $settings->delete('versions_of_last_sent_mail');

        } catch (Exception $e) {

            ilLoggerFactory::getLogger(self::PLUGIN_ID)->log($e->getMessage());

        }

        return parent::beforeUninstall();
    }
}
