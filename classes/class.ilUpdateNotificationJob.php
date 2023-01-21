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
     * @var string Info about how to create the notifications, dismissible or permanent.
     */
    public const DEFAULT_INSISTENCE = 'middle';
    /**
     * @var string User group that will be notified, by default only admins.
     */
    public const DEFAULT_USER_GROUPS = ilUpdateNotificationPlugin::ADMIN_ROLE_IDS;
    /**
     * @var string The url is used to check if there is a newer version of Ilias on github.
     */
    public const DEFAULT_UPDATE_URL = 'https://github.com/ILIAS-eLearning/ILIAS/releases/tag/v';
    /**
     * @var string By default, there will be no emails sent.
     */
    public const DEFAULT_EMAIL_RECIPIENTS = [ilUpdateNotificationPlugin::ADMIN_ROLE_ID];

    /**
     * Timeout for curl
     */
    public const CURLOPT_TIMEOUT = '3';

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
            'high' => ilUpdateNotificationPlugin::getInstance()->txt("high_option"),
            'middle' => ilUpdateNotificationPlugin::getInstance()->txt("middle_option"),
            'low' => ilUpdateNotificationPlugin::getInstance()->txt("low_option"),
        ];

        $insistence = new ilSelectInputGUI(
            ilUpdateNotificationPlugin::getInstance()->txt("insistence_caption"),
            "insistence"
        );
        $insistence->setOptions($options);
        $insistence->setInfo(ilUpdateNotificationPlugin::getInstance()->txt('insistence_info'));
        $insistence->setValue($this->settings->get('insistence', self::DEFAULT_INSISTENCE));

        $a_form->addItem($insistence);

        $available_roles = $this->getRoles(ilRbacReview::FILTER_ALL_GLOBAL);
        $role_multi_select = new ilMultiSelectInputGUI(
            ilUpdateNotificationPlugin::getInstance()->txt("user_groups_caption"),
            'user_groups'
        );
        $role_multi_select->setOptions($available_roles);
        $role_multi_select->setInfo(ilUpdateNotificationPlugin::getInstance()->txt('user_groups_info'));
        $role_multi_select->setValue($this->getUserGroupsValue());

        $a_form->addItem($role_multi_select);

        $update_url = new ilTextInputGUI(
            ilUpdateNotificationPlugin::getInstance()->txt("update_url_caption"),
            'update_url'
        );
        $update_url->setInfo(ilUpdateNotificationPlugin::getInstance()->txt("update_url_info"));
        $update_url->setValue($this->getUpdateUrl());
        $update_url->setRequired(true);
        $a_form->addItem($update_url);

        $email_multi_select = new ilMultiSelectInputGUI(
            ilUpdateNotificationPlugin::getInstance()->txt("email_recipients_caption"),
            'email_recipients'
        );
        $email_multi_select->setOptions($available_roles);
        $email_multi_select->setInfo(ilUpdateNotificationPlugin::getInstance()->txt('email_recipients_info'));
        $email_multi_select->setValue($this->getEmailRecipientGroupsValue());

        $a_form->addItem($email_multi_select);

    }

    /**
     * Gets all available roles in the system via filter
     * @see ilADNNotificationUIFormGUI::getRoles()
     * @param $filter
     * @return array|int[]
     */
    protected function getRoles($filter) : array
    {
        global $DIC;
        $opt = [];
        foreach ($DIC->rbac()->review()->getRolesByFilter($filter) as $role) {
            $opt[$role['obj_id']] = $role['title'] . ' (ID: ' . $role['obj_id'] . ')';
        }

        return $opt;
    }

    /**
     * Gets all available role ids
     * @param $filter
     * @return array|int[]
     */
    protected function getAllRoleIds($filter) : array
    {
        global $DIC;
        $opt = [];
        foreach ($DIC->rbac()->review()->getRolesByFilter($filter) as $role) {
            $opt[] = $role['obj_id'];
        }

        return $opt;
    }


    /**
     * @inheritDoc
     */
    public function saveCustomSettings(ilPropertyFormGUI $a_form) :bool
    {
        $this->settings->set('insistence', $a_form->getInput('insistence'));
        $this->settings->set('update_url', $a_form->getInput('update_url'));
        $this->settings->set('user_groups', json_encode($a_form->getInput('user_groups')));
        $this->settings->set('email_recipients', json_encode($a_form->getInput('email_recipients')));
        return true;
    }

    public function isLimitedToRoles() : bool {
        $all_available_roles = $this->getAllRoleIds(ilRbacReview::FILTER_ALL_GLOBAL);
        $roles = json_decode($this->settings->get('user_groups', self::DEFAULT_USER_GROUPS));
        return ($roles != $all_available_roles);
    }

    /**
     * Returns an array with all role ids to notify via email. [Default are all admins.]
     * @return array|null Array with group ids (as strings)
     */
    public function getEmailRecipientGroupsValue() : ?array {
        $x = json_encode(self::DEFAULT_EMAIL_RECIPIENTS);
        return json_decode($this->settings->get('email_recipients', $x));
    }

    # Does not work yet will be used in the future to notify via role distributor...
    public function getEmailRecipientGroups() : array {
        $email_recipients = self::DEFAULT_EMAIL_RECIPIENTS;
        $group_ids = json_decode($this->settings->get('email_recipients', self::DEFAULT_EMAIL_RECIPIENTS));
        foreach ($group_ids as $group_id) {
            $email_recipients[] = "#il_role_{$group_id}";
        }
        return $email_recipients;
    }
    # Solution to make it work because getEmailRecipientGroups does not work.
    public function getEmailRecipients(int $group_id) : array {
        $email_recipients = [];

        global $DIC;

        foreach ($DIC->rbac()->review()->assignedUsers($group_id) as $user_id) {
            $name = ilObjUser::_lookupName($user_id);
            $email_recipients[$user_id] = $name['login'];
        }

        return $email_recipients;
    }

    /**
     * Returns all groups to notify via anouncement
     * @return array|null Array with group ids (as strings)
     */
    public function getUserGroupsValue() : ?array {
        return json_decode($this->settings->get('user_groups', self::DEFAULT_USER_GROUPS));
    }

    /** Returns a title for an adn notification
     * @return string title
     */
    public function getNotificationTitle() :string
    {
        return sprintf(ilUpdateNotificationPlugin::getInstance()->txt("notification_title"), date('[d.m.Y]'));
    }

    public function getUpdateUrl(string $version = '') :string
    {
        return $this->settings->get('update_url', self::DEFAULT_UPDATE_URL)."$version";
    }

    /** Returns the insistence level (high/middle/low)
     * @return string insistence level
     */
    public function getInsistenceLevel() :string
    {
        return $this->settings->get('insistence', self::DEFAULT_INSISTENCE);
    }

    /** Whether a notification is dismissible or permanent
     * @return bool is dismissible?
     */
    public function getDismissible() :bool
    {
        return ($this->getInsistenceLevel() != 'high');
    }

    public function setNotificationId(int $id) :void
    {
        $this->settings->set('notification_id', $id);
    }

    public function setMinorNotificationId(int $id) :void
    {
        $this->settings->set('minor_notification_id', $id);
    }

    public function setMajorNotificationId(int $id) :void
    {
        $this->settings->set('major_notification_id', $id);
    }

    public function getNotificationId() :int
    {
        return intval($this->settings->get('notification_id','0'));
    }

    public function getMinorNotificationId() :int
    {
        return intval($this->settings->get('minor_notification_id','0'));
    }

    public function getMajorNotificationId() :int
    {
        return intval($this->settings->get('major_notification_id','0'));
    }

    public function setNewestMinorVersion(string $version) :void
    {
        $this->settings->set('newest_minor_version', $version);
    }

    public function getNewestMinorVersionFromSettings() :string
    {
        return $this->settings->get('newest_minor_version');
    }

    public function setNextMajorVersion(string $version) :void
    {
        $this->settings->set('next_major_version', $version);
    }

    public function getNextMajorVersionFromSettings() :string
    {
        return $this->settings->get('next_major_version');
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
        $il_adn_notification->setType(ilADNNotification::TYPE_ERROR);
        $il_adn_notification->setTypeDuringEvent(ilADNNotification::TYPE_ERROR);
        $il_adn_notification->setDismissable($this->getDismissible());
        $il_adn_notification->setPermanent(true);
        $il_adn_notification->setActive(true);
        $il_adn_notification->setLimitToRoles($this->isLimitedToRoles());
        if($this->isLimitedToRoles()) {
            $il_adn_notification->setLimitedToRoleIds($this->getUserGroupsValue());
        }
        $il_adn_notification->setEventStart(new DateTimeImmutable('now'));
        $il_adn_notification->setEventEnd(new DateTimeImmutable('now'));
        $il_adn_notification->setDisplayStart(new DateTimeImmutable('now'));
        $il_adn_notification->setDisplayEnd(new DateTimeImmutable('now'));

        $il_adn_notification->create();

        $this->setNotificationId($il_adn_notification->getId());
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
        if($this->isLimitedToRoles()) {
            $il_adn_notification->setLimitedToRoleIds($this->getUserGroupsValue());
        }
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
            $il_adn_notification->delete();
        } catch (\Exception $ex) {
            ilLoggerFactory::getLogger(ilUpdateNotificationPlugin::PLUGIN_ID)->log($ex->getMessage());
            throw $ex;
        }
    }

    /** Returns the body string for a notification
     * @param string $newest_version_numeric number of the newest version e.g. 8.15
     * @param string $newest_minor_version number of the newest minor version of current version e.g. 7.15
     * @param string $url url to the newest version on GitHub for example
     * @return string the body string for a notification
     */
    public function getNotificationBody(string $newest_version_numeric, string $newest_minor_version, string $url='#', string $major_release_url='#') :string
    {
        $version_numeric = ILIAS_VERSION_NUMERIC;
        $body = ($newest_version_numeric == $newest_minor_version)?
            ilUpdateNotificationPlugin::getInstance()->txt("notification_body_minor"):ilUpdateNotificationPlugin::getInstance()->txt("notification_body_combined");

        # Not combined: at this point only minor update
        $body = str_replace('[INSTALLED_VERSION]', $version_numeric, $body);
        $body = str_replace('[RELEASE_VERSION]', $newest_version_numeric, $body);
        $body = str_replace('[RELEASE_URL]', $url, $body);
        # In case combined ... TODO Separate into 2 or 3 functions
        $body = str_replace('[MINOR_RELEASE_URL]', $url, $body);
        $body = str_replace('[MINOR_RELEASE_VERSION]', $newest_minor_version, $body);
        $body = str_replace('[MAJOR_RELEASE_URL]', $major_release_url, $body);
        $body = str_replace('[MAJOR_RELEASE_VERSION]', $newest_version_numeric, $body);

        return $body;
    }

    /** Returns the body string for an email
     * @param string $newest_version_numeric number of the newest version e.g. 7.15
     * @param string $url url to the newest version on GitHub for example
     * @return string the body string for an email
     */
    public function getMailBody(string $newest_version_numeric, string $newest_minor_version,string $url='#') :string
    {
        $version_numeric = ILIAS_VERSION_NUMERIC;
        $body = ($newest_version_numeric == $newest_minor_version)?
            ilUpdateNotificationPlugin::getInstance()->txt('email_body_minor'):ilUpdateNotificationPlugin::getInstance()->txt('email_body_combined');

        $body = str_replace('[INSTALLED_VERSION]', $version_numeric, $body);
        $body = str_replace('[MAJOR_RELEASE_VERSION]', $newest_version_numeric, $body);
        $body = str_replace('[MINOR_RELEASE_VERSION]', $newest_minor_version, $body);
        $body = str_replace('[MINOR_RELEASE_URL]', $url, $body);
        $body = str_replace('[MAJOR_RELEASE_URL]', $url, $body);
        $body = str_replace('\n', PHP_EOL, $body);

        return $body;
    }

    /** Indicates whether this url exists or not (404 = does not exist)
     * @param String $url url to check
     * @return array with status code (e.g. 200/404) and html content
     */
    public function fetchUrl(string $url) :array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURLOPT_TIMEOUT);
        $content = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
          'status_code' => $status_code,
          'content' => $content
        ];
    }

    /** Checks whether this url exists or not.
     * @param String $url url to check
     * @return bool true if exists
     */
    public function checkUrl(string $url) :bool
    {
        $result = $this->fetchUrl($url);

        if ($result['status_code'] == 404) {
            return false;
        } else if ($result['content'] === false or empty($result['content'])) {
            return false;
        }
        else {
            return true;
        }
    }

    /** Returns the newest (stable) major version (e.g. 7.0 or 8.0) as string. Checks for up to +5 major versions ahead and returns the first found.
     * @return string the newest (stable) major version (e.g. 7.0 or 8.0) as string
     * @throws Exception
     */
    public function getNextMajorVersion() :string
    {
        $current_version_numeric = strval(ILIAS_VERSION_NUMERIC);
        $major = intval(explode('.', $current_version_numeric)[0]);
        $minor = 0;
        if ($major <= 0) {
            throw new \Exception("Major version cannot be 0 or lower!");
        }

        for ($i = 0; $i <= 5; $i++) {
            $major = ($major + 1);

            $url = $this->getUpdateUrl( $major . '.' . $minor);

            sleep(2);
            if ($this->checkUrl($url)) {
                return $this->getNewestMinorVersion("$major.$minor");
            }
        }

        return $this->getNewestMinorVersion($current_version_numeric);
    }

    /** Returns the newest (stable) minor version (e.g. 7.11 or 7.13 ...) as string
     * @return string the newest (stable) major version (e.g. 7.11 or 7.13 ...) as string
     * @throws Exception
     */
    public function getNewestMinorVersion(string $current_version_numeric = null) :string
    {
        if(is_null($current_version_numeric)) {
            $current_version_numeric = strval(ILIAS_VERSION_NUMERIC);
        }
        $major = intval(explode('.', $current_version_numeric)[0]);
        $minor = intval(explode('.', $current_version_numeric)[1]);
        if ($major <= 0) {
            throw new \Exception("Major version cannot be 0 or lower!");
        }
        for ($i = 0; $i <= 20; $i++) {

            $url = $this->getUpdateUrl($major.'.'.$minor);

            sleep(2);
            if ($this->checkUrl($url)) {
                $minor++;
            } else {
                $minor = $minor - 1;
                break;
            }
        }

        return "$major.$minor";
    }

    /** Sends an emails to one or many recipients
     * @param array  $recipients recipients in a string array ['username1','username2']
     * @param string $body message body to send
     * @return void
     */
    public function sendMails(array $recipients, string $body)
    {
        global $DIC;
        $mail = new ilMail($DIC->user()->id);

        # This does not send mails to all users of a role, and does not replace placeholders like [Firstname]...
        //$mail = new ilSystemNotification();
        //$mail->setSubjectDirect($this->getNotificationTitle());
        //$mail->setIntroductionDirect($body);
        //$mail->sendMail($recipients); #il_role_2{id}

        foreach ($recipients as $recipient) {
            if(empty($recipient)) { # usernames are being used
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

        $current_version_numeric = strval(ILIAS_VERSION_NUMERIC);

        $next_major_version_numeric = $this->getNextMajorVersion();
        $newest_minor_version_numeric = $this->getNewestMinorVersion($current_version_numeric);

        $newest_version_url = $this->getUpdateUrl($next_major_version_numeric);

        $insistence_level = $this->getInsistenceLevel();

        if ($current_version_numeric != $next_major_version_numeric || $current_version_numeric != $newest_minor_version_numeric) {

            if ($insistence_level == 'low')
            {
                ilLoggerFactory::getLogger(ilUpdateNotificationPlugin::PLUGIN_ID)->log(sprintf(
                    ilUpdateNotificationPlugin::getInstance()->txt('log_body'),
                    $current_version_numeric,
                    $next_major_version_numeric,
                    $newest_minor_version_numeric

                ));
                $result->setMessage(sprintf(
                    ilUpdateNotificationPlugin::getInstance()->txt('log_body'),
                    $current_version_numeric,
                    $next_major_version_numeric,
                    $newest_minor_version_numeric
                ));
                return $result;
            }

            /*
             * TODO check for getMinor...Id and getMajor...Id -> create new notifications for each. Right now only the body differs.
             * if (MajorVersion != MinorVersion) => createMajorNotification($next_major_version) and createMinorNotification($newest_minor_version) ELSE: createMinorNotification($newest_minor_version)
             * inside createMajorNotification : save id, "output"/save different body
             * inside createMinorNotification : save id, "output"/save different body
             * same for e-mail
             */
            if (!empty($this->getNotificationId())) {
                $this->updateNotification(
                    $this->getNotificationId(),
                    $this->getNotificationBody(
                        $next_major_version_numeric,
                        $newest_minor_version_numeric,
                        $newest_version_url
                    )
                );
            } else {
                $this->createNotification(
                    $this->getNotificationBody(
                        $next_major_version_numeric,
                        $newest_minor_version_numeric,
                        $newest_version_url
                    )
                );
            }

            $mail_sent = false;
            $versions_of_last_sent_mail = $this->settings->get("versions_of_last_sent_mail", "");
            // Send only one mail for every new version.
            if("$next_major_version_numeric,$newest_minor_version_numeric" != $versions_of_last_sent_mail){

                $email_recipients_groups = $this->getEmailRecipientGroupsValue();

                if (!empty($email_recipients_groups)) {
                    foreach ($email_recipients_groups as $group_id) {
                        $email_recipients = $this->getEmailRecipients(intval($group_id));

                        if (!empty($email_recipients)) {
                            $this->sendMails(
                                $email_recipients,
                                $this->getMailBody(
                                    $next_major_version_numeric,
                                    $newest_minor_version_numeric,
                                    $newest_version_url
                                )
                            );
                        }
                    }
                    $mail_sent = true;
                }
                $this->settings->set('versions_of_last_sent_mail',"$next_major_version_numeric,$newest_minor_version_numeric");
            }

            $result->setMessage(sprintf(
                ilUpdateNotificationPlugin::getInstance()->txt('log_body'),
                $current_version_numeric,
                $next_major_version_numeric,
                $newest_minor_version_numeric
            ).(($mail_sent)?' (Mail(s) sent)':'')
            .(' Notification ID '.$this->getNotificationId()));

            $this->setNextMajorVersion($next_major_version_numeric);
            $this->setNewestMinorVersion($newest_minor_version_numeric);

            return $result;
        }
        else {
            if(!empty($this->getNotificationId()))
                $this->removeNotification($this->getNotificationId());
            $result->setMessage('Die Version ist aktuell!');
            return $result;
        }
    }
}
