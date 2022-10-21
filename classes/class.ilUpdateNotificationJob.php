<?php

declare(strict_types=1); //strict_types declaration must be the very first statement in the script

/**
 * @property ilSetting $settings
 */
class ilUpdateNotificationJob extends ilCronJob
{
    /**
     * @var string Contains the job id.
     */
    public const JOB_ID = 'UpdateNotificationJob';
    /**
     * @var string Contains the job name.
     */
    public const JOB_NAME = ilUpdateNotificationPlugin::PLUGIN_NAME.' CronJob';

    /**
     * @var string Info about whether to check for minor or only for major versions.
     */
    public const DEFAULT_LEVEL = 'minor';
    /**
     * @var string Info about how to create the notifications, dismissible or permanent.
     */
    public const DEFAULT_INSISTENCE = 'middle';
    /**
     * @var string User group that will be notified, by default only admins.
     */
    public const DEFAULT_USER_GROUPS = 'admins';
    /**
     * @var string The url is used to check if there is a newer version of Ilias on github.
     */
    public const DEFAULT_UPDATE_URL = 'https://github.com/ILIAS-eLearning/ILIAS/releases/tag/v';
    /**
     * @var string By default, there will be no emails sent.
     */
    public const DEFAULT_EMAIL_RECIPIENTS = '';

    /**
     * @var ilSetting Contains and manages the plugin settings.
     */
    protected $settings;


    public function __construct()
    {
        $this->settings = new ilSetting(ilUpdateNotificationPlugin::PLUGIN_ID);
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::JOB_ID;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return self::JOB_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return ilUpdateNotificationPlugin::getInstance()->txt("cron_description");
    }

    /**
     * @inheritDoc
     */
    public function hasAutoActivation(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleType(): int
    {
        return ilCronJob::SCHEDULE_TYPE_DAILY;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function hasCustomSettings(): bool
    {
        return true;
    }

    /**
     * @ineritDoc
     */
    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form)
    {

        $options = [
            'minor' => ilUpdateNotificationPlugin::getInstance()->txt("minor_option"),
            'major' => ilUpdateNotificationPlugin::getInstance()->txt("major_option"),
        ];

        $level = new ilSelectInputGUI(
            'Überprüfung',
            'level'
        );
        $level->setOptions($options);
        $level->setInfo(ilUpdateNotificationPlugin::getInstance()->txt("level_info"));
        $level->setValue($this->settings->get('level', self::DEFAULT_LEVEL));

        $a_form->addItem($level);

        $options = [
            'high' => ilUpdateNotificationPlugin::getInstance()->txt("high_option"),
            'middle' => ilUpdateNotificationPlugin::getInstance()->txt("middle_option"),
            'low' => ilUpdateNotificationPlugin::getInstance()->txt("low_option"),
        ];

        $insistence = new ilSelectInputGUI(
            'Benachrichtigungen',
            "insistence"
        );
        $insistence->setOptions($options);
        $insistence->setInfo(ilUpdateNotificationPlugin::getInstance()->txt('insistence_info'));
        $insistence->setValue($this->settings->get('insistence', self::DEFAULT_INSISTENCE));

        $a_form->addItem($insistence);

        ###

        $options = [
            'all' => ilUpdateNotificationPlugin::getInstance()->txt('all_user_groups_option'),
            'admins' => ilUpdateNotificationPlugin::getInstance()->txt('admin_user_groups_option'),
        ];

        $user_groups = new ilSelectInputGUI(
            'Nutzergruppen',
            "user_groups"
        );
        $user_groups->setOptions($options);
        $user_groups->setInfo(ilUpdateNotificationPlugin::getInstance()->txt('user_groups_info'));
        $user_groups->setValue($this->settings->get('user_groups', self::DEFAULT_USER_GROUPS));

        $a_form->addItem($user_groups);

        ###

        $update_url = new ilTextInputGUI(
            'Update Check URL',
            'update_url'
        );
        $update_url->setInfo(ilUpdateNotificationPlugin::getInstance()->txt("update_url_info"));
        $update_url->setValue($this->settings->get('update_url', self::DEFAULT_UPDATE_URL));
        $update_url->setRequired(true);
        $a_form->addItem($update_url);

        $email_recipients = new ilTextInputGUI(
            'Email Empfänger',
            'email_recipients'
        );
        $email_recipients->setInfo(ilUpdateNotificationPlugin::getInstance()->txt("email_recipients_info"));
        $email_recipients->setValue($this->settings->get('email_recipients', self::DEFAULT_EMAIL_RECIPIENTS));
        $a_form->addItem($email_recipients);
    }

    /**
     * @inheritDoc
     */
    public function saveCustomSettings(ilPropertyFormGUI $a_form) :bool
    {
        $this->settings->set('level', $a_form->getInput('level'));
        $this->settings->set('insistence', $a_form->getInput('insistence'));
        $this->settings->set('update_url', $a_form->getInput('update_url'));
        $this->settings->set('email_recipients', $a_form->getInput('email_recipients'));
        $this->settings->set('user_groups', $a_form->getInput('user_groups'));
        return true;
    }

    public function isLimitedToRoles() : bool {
        return ($this->settings->get('user_groups', self::DEFAULT_USER_GROUPS) != 'all');
    }

    public function getUsergroupsValue() : ?array {
        switch($this->settings->get('user_groups', self::DEFAULT_USER_GROUPS)) {
            case 'admins':
                return ilUpdateNotificationPlugin::ADMIN_ROLE_IDS;
            default:
                return null;
        }
    }

    /** Return an array of email addresses
     * @return array recipients split by ;
     */
    public function getEmailRecipients() : array
    {
        $recipients_str = $this->settings->get('email_recipients', self::DEFAULT_EMAIL_RECIPIENTS);

        if (str_contains($recipients_str,';'))
            $recipients = explode(';',$recipients_str);
        else
            $recipients[0] = $recipients_str;

        return $recipients;
    }

    /** Returns a title for an adn notification
     * @return string title
     */
    public function getNotificationTitle() :string
    {
        return sprintf("Update Notification %s", date('[d.m.Y]'));
    }

    /** Returns the insistence level (high/middle/low)
     * @return string insistence level
     */
    public function getInsistenceLevel() :string
    {
        return $this->settings->get('email_recipients', self::DEFAULT_INSISTENCE);
    }

    /** Returns info about when to notify (for major or minor version)
     * @return string level
     */
    public function getLevel() :string
    {
        return $this->settings->get('level', self::DEFAULT_LEVEL);
    }

    /** Whether a notification is dismissible or permanent
     * @return bool is dismissible?
     */
    public function getDismissible() :bool
    {
        return ($this->getInsistenceLevel() != 'high');
    }

    /**
     * Updates an adn notification. Dismissed are saved in il_adn_dismiss.
     * @param int         $id of the notification to identify
     * @param String      $body the string(html) body of the notification
     * @return void
     */
    public function updateNotification(int $id, String $body) :void
    {
        $il_adn_notification = new ilADNNotification($id);
        $il_adn_notification->setTitle($this->getNotificationTitle());
        $il_adn_notification->setActive(true);
        $il_adn_notification->setBody($body);
        $il_adn_notification->setDismissable($this->getDismissible());
        $il_adn_notification->resetForAllUsers();
        $il_adn_notification->setLimitToRoles($this->isLimitedToRoles());
        if($this->isLimitedToRoles())
            $il_adn_notification->setLimitedToRoleIds($this->getUsergroupsValue());
        $il_adn_notification->update();

    }

    /**
     * Deletes an adn notification.
     * @param int $id of the notification to identify
     * @return void
     * @throws Exception
     */
    public function removeNotification(int $id) :void
    {
        try {
            $il_adn_notification = new ilADNNotification($id);
            $il_adn_notification->setActive(false);
            $il_adn_notification->setDisplayEnd((new \DateTimeImmutable('now')));
            $il_adn_notification->update();
        } catch (\Exception $ex) {
            ilLoggerFactory::getLogger(ilUpdateNotificationPlugin::PLUGIN_ID);
            throw $ex;
        }
    }

    /** Returns the body string for a notification
     * @param string $newest_version_numeric number of the newest version e.g. 7.15
     * @param string $url url to the newest version on GitHub for example
     * @return string the body string for a notification
     */
    public function getNotificationBody(string $newest_version_numeric, string $url='#') :string
    {
        $version_numeric = ILIAS_VERSION_NUMERIC;
        return sprintf(
            ilUpdateNotificationPlugin::getInstance()->txt("notification_body"),
            $version_numeric,
            $newest_version_numeric,
            $url
        );
    }

    /** Returns the body string for an email
     * @param string $newest_version_numeric number of the newest version e.g. 7.15
     * @param string $url url to the newest version on GitHub for example
     * @return string the body string for an email
     */
    public function getMailBody(string $newest_version_numeric, string $url='#') :string
    {
        $version_numeric = ILIAS_VERSION_NUMERIC;
        return sprintf(
            ilUpdateNotificationPlugin::getInstance()->txt("email_body"),
            $version_numeric,
            $newest_version_numeric,
            $url
        );
    }

    /**
     * Creates an adn notification. Dismissed are saved in il_adn_dismiss.
     * @param String      $body the string(html) body of the notification
     * @return void
     */
    public function createNotification(String $body): void
    {
        $il_adn_notification = new ilADNNotification();
        $il_adn_notification->setTitle($this->getNotificationTitle());
        $il_adn_notification->setBody($body);
        $il_adn_notification->setType(3);
        $il_adn_notification->setTypeDuringEvent(3);
        $il_adn_notification->setDismissable($this->getDismissible());
        $il_adn_notification->setPermanent(true);
        $il_adn_notification->setActive(true);
        $il_adn_notification->setLimitToRoles($this->isLimitedToRoles());
        if($this->isLimitedToRoles())
            $il_adn_notification->setLimitedToRoleIds($this->getUsergroupsValue());
        $il_adn_notification->setEventStart(new DateTimeImmutable('now'));
        $il_adn_notification->setEventEnd(new DateTimeImmutable('now'));
        $il_adn_notification->setDisplayStart(new DateTimeImmutable('now'));
        $il_adn_notification->setDisplayEnd(new DateTimeImmutable('now'));

        $il_adn_notification->create();
    }

    /** Returns an array with infos about other notifications in the database (Containing "Update Notification" in its title)
     * @return array|int[] an array with infos about other notifications in the database
     * @throws Exception
     */
    public function getCurrentNotificationInfo() :array
    {
        /**
         * @var $ilDB ilDBInterface
         */
        global $ilDB;

        if (!$ilDB->tableExists('il_adn_notifications')) { throw new Exception('il_adn_notifications does not exist!'); }

        $set = $ilDB->query($this->getCurrentNotificationInfoQuery());
        $records = $ilDB->fetchAssoc($set);
        if (!empty($records)) {
            $ids = intval($records['entity_amount']);
            $highest_id = intval($records['highest_id']);
            $newest_date = intval($records['newest_date']);

            return [
                'id' => $highest_id,
                'created' => $newest_date,
                'amount_of_notifications' => $ids,
            ];
        }
        return [
            'id' => 0,
            'created' => 0,
            'amount_of_notifications' => 0,
        ];
    }

    /** Query that returns infos about notifications
     * @return string mysql query
     */
    public function getCurrentNotificationInfoQuery() :string {
        return "SELECT count(`id`) as `entity_amount`, max(`id`) as `highest_id`, max(`create_date`) as `newest_date` FROM il_adn_notifications WHERE `title` LIKE '%Update Notification%' AND `active` = 1;";
    }

    /** Checks whether this url exists or not (404 = does not exist)
     * @param String $url url to check
     * @return array with status code (e.g. 200/404) and html content
     */
    public function checkUrl(string $url) :array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, '3');
        $content = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
          'status_code' => $status_code,
          'content' => $content
        ];
    }

    /** Returns the newest (stable) major version (e.g. 7.0 or 8.0) as string
     * @return string the newest (stable) major version (e.g. 7.0 or 8.0) as string
     * @throws Exception
     */
    public function getNewestMayorVersion() :string
    {
        $newest_version_numeric = strval(ILIAS_VERSION_NUMERIC);
        $major = intval(explode('.', $newest_version_numeric)[0]);
        $minor = 0;
        if ($major <= 0) {
            throw new \Exception("Major version cannot be 0 or lower!");
        }
        $major = ($major + 1);

        $url = $this->settings->get('update_url', self::DEFAULT_UPDATE_URL).$major.'.'.$minor;

        $result = $this->checkUrl($url);

        if ($result['status_code'] == 404) {
            return $newest_version_numeric;
        } else if ($result['content'] === false or empty($result['content'])) {
            return $newest_version_numeric;
        }
        else {
            return "$major.$minor";
        }
    }

    /** Returns the newest (stable) minor version (e.g. 7.11 or 7.13 ...) as string
     * @return string the newest (stable) major version (e.g. 7.11 or 7.13 ...) as string
     * @throws Exception
     */
    public function getNewestMinorVersion(string $newest_version_numeric = null) :string
    {
        if(is_null($newest_version_numeric)) {
            $newest_version_numeric = strval(ILIAS_VERSION_NUMERIC);
        }
        $major = intval(explode('.', $newest_version_numeric)[0]);
        $minor = intval(explode('.', $newest_version_numeric)[1]);
        if ($major <= 0) {
            throw new \Exception("Major version cannot be 0 or lower!");
        }
        for ($i = 0; $i <= 20; $i++) {

            $url = $this->settings->get('update_url', self::DEFAULT_UPDATE_URL).$major.'.'.$minor;

            $result = $this->checkUrl($url);

            if ($result['status_code'] === 404) {
                $minor = $minor - 1;
                break;
            }
            if ($result['content'] === false or empty($result['content'])) {
                $minor = $minor - 1;
                break;
            } else {
                $minor++;
            }
        }

        return "$major.$minor";
    }

    /** Sends an emails to one or many recipients
     * @param array  $recipients recipients in an string array ['john.doe@example.com','test@example.com']
     * @param string $body message body to send
     * @return void
     */
    public function sendMail(array $recipients, string $body)
    {
        /** @var ILIAS\DI\Container $DIC */
        global $DIC;
        $sender = $DIC->user()->getId();
        $firstname = $DIC->user()->getFirstname();
        $lastname = $DIC->user()->getLastname();

        $body = str_replace('\n',PHP_EOL, $body);
        if(!empty($firstname))
            $body = str_replace('[FIRST_NAME]', $firstname, $body);
        if(!empty($lastname))
            $body = str_replace('[LAST_NAME]', $lastname, $body);

        $mail = new ilMail($sender);

        foreach ($recipients as $recipient) {
            if(empty($recipient) OR !str_contains($recipient,'@')) {
                continue;
            }
            $mail->sendMail(
                $recipient,
                "",
                "",
                $this->getNotificationTitle(),
                $body,
                [],
                true
            );
        }

    }


    /**
     * @inheritDoc
     * @throws Exception
     */
    public function run(): ilCronJobResult
    {
        $result = new ilCronJobResult();
        $result->setStatus(ilCronJobResult::STATUS_OK);
        $result->setCode(200);

        $info = $this->getCurrentNotificationInfo();

        $version_numeric = strval(ILIAS_VERSION_NUMERIC);

        if($this->getLevel() == 'minor')
        {
            $newest_version_numeric = $this->getNewestMayorVersion();
            $newest_version_numeric = $this->getNewestMinorVersion($newest_version_numeric);
        }
        else {
            $newest_version_numeric = $this->getNewestMayorVersion();
        }

        $url = $this->settings->get('update_url', self::DEFAULT_UPDATE_URL).$newest_version_numeric;

        $insistence_level = $this->getInsistenceLevel();

        if ($version_numeric != $newest_version_numeric) {

            if ($insistence_level == 'low')
            {
                ilLoggerFactory::getLogger(ilUpdateNotificationPlugin::PLUGIN_ID)->log(sprintf(
                    ilUpdateNotificationPlugin::getInstance()->txt('log_body'),
                    $version_numeric,
                    $newest_version_numeric
                ));
                $result->setMessage(sprintf(
                    ilUpdateNotificationPlugin::getInstance()->txt('log_body'),
                    $version_numeric,
                    $newest_version_numeric
                ));
                return $result;
            }

            if ($info['amount_of_notifications'] > 0) {
                $this->updateNotification(
                    $info['id'],
                    $this->getNotificationBody($newest_version_numeric, $url)
                );
            } else {
                $this->createNotification(
                    $this->getNotificationBody($newest_version_numeric, $url)
                );
            }

            $email_recipients = $this->getEmailRecipients();
            if (!empty($email_recipients[0])) {
                $this->sendMail(
                    $email_recipients,
                    $this->getMailBody($newest_version_numeric, $url)
                );
            }

            $result->setMessage(sprintf(
                ilUpdateNotificationPlugin::getInstance()->txt('log_body'),
                $version_numeric,
                $newest_version_numeric
            ));
            return $result;
        }
        else {
            $this->removeNotification($info['id']);
            $result->setMessage('Die Version ist aktuell!');
            return $result;
        }
    }
}
