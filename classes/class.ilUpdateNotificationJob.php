<?php

declare(strict_types=1); //strict_types declaration must be the very first statement in the script

class ilUpdateNotificationJob extends ilCronJob
{

    /**
     * @var ilSetting
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
        return "HelloWorldJob";
    }

    public function getTitle(): string
    {
        return 'Chris amazing '.ilUpdateNotificationPlugin::PLUGIN_NAME.' CronJob';
    }

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
     * @return bool
     */
    public function hasCustomSettings(): bool
    {
        return true;
    }

    /**
     * @param ilPropertyFormGUI $a_form
     */
    public function addCustomSettingsToForm(ilPropertyFormGUI $a_form)
    {

        $options = [
            'minor' => 'Prüfe Minor- & Major-Updates (empfohlen)',
            'major' => 'Prüfe nur Major-Updates',
        ];

        $level = new ilSelectInputGUI(
            'Überprüfung',
            'level'
        );
        $level->setOptions($options);
        $level->setInfo('Wie streng soll das Plugin die Ilias Version überprüfen?');
        $level->setValue($this->settings->get('level', 'minor'));

        $a_form->addItem($level);

        $options = [
            'high' => 'Streng (Können nicht geschlossen werden!)', // TODO Müssen nach update entfernt werden (wenn $version = $newest_version)
            'middle' => 'Mittel (Können geschlossen werden, werden nicht erneut angezeigt) [Empfohlen]',
            'low' => 'Schwach (Es gibt nur einen Log-Eintrag!)',
        ];

        $insistence = new ilSelectInputGUI(
            'Benachrichtigungen',
            "insistence"
        );
        $insistence->setOptions($options);
        $insistence->setInfo('Wie streng sollen die Benachrichtigungen sein?');
        $insistence->setValue($this->settings->get('insistence', 'middle'));

        $a_form->addItem($insistence);

        $update_url = new ilTextInputGUI('Update Check URL', 'update_url');
        $update_url->setInfo('Zum Überprüfen genutzte URL. Sollte nicht geändert werden wenn Sie nicht genau wissen was diese Einstellung bedeutet!');
        $update_url->setValue($this->settings->get('update_url', 'https://github.com/ILIAS-eLearning/ILIAS/releases/tag/v'));
        $update_url->setRequired(true);
        $a_form->addItem($update_url);

        $email_recipients = new ilTextInputGUI('Email Empfänger', 'email_recipients');
        $email_recipients->setInfo('Leer = Keine Emails, Mehrere Empfänger mit Semicolon (;) trennen.');
        $email_recipients->setValue($this->settings->get('email_recipients', ''));
        $a_form->addItem($email_recipients);
    }

    /**
     * @param ilPropertyFormGUI $a_form
     * @return bool
     */
    public function saveCustomSettings(ilPropertyFormGUI $a_form)
    {
        $this->settings->set('level', $a_form->getInput('level'));
        $this->settings->set('insistence', $a_form->getInput('insistence'));
        $this->settings->set('update_url', $a_form->getInput('update_url'));
        $this->settings->set('email_recipients', $a_form->getInput('email_recipients'));
        return true;
    }

    /** @return array recipients split by ; */
    public function getEmailRecipients() : array
    {
        $recipients_str = $this->settings->get('email_recipients', '');

        if (str_contains($recipients_str,';'))
            $recipients = explode(';',$recipients_str);
        else
            $recipients[0] = $recipients_str;

        return $recipients;
    }

    public function getNotificationTitle() :string
    {
        return sprintf("Update Notification %s", date('[d.m.Y]'));
    }

    public function getInsistenceLevel() :string
    {
        return $this->settings->get('insistence', 'middle');
    }

    public function getLevel() :string
    {
        return $this->settings->get('level', 'minor');
    }

    public function getDismissable() :bool
    {
        return ($this->getInsistenceLevel() != 'high');
    }

    /**
     * Dissmissed are saved in il_adn_dismiss. Reset Notifications
     * @param int         $id
     * @param String      $message
     * @param String|null $title
     * @return void
     */
    public function updateNotification(int $id, String $body) :void
    {
        $il_adn_notification = new ilADNNotification($id);
        $il_adn_notification->setTitle($this->getNotificationTitle());
        $il_adn_notification->setActive(true);
        $il_adn_notification->setBody($body);
        $il_adn_notification->setDismissable($this->getDismissable());
        $il_adn_notification->resetForAllUsers();
        $il_adn_notification->update();

    }

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

    public function getNotificationBody($newest_version_numeric, $url='#') :string
    {
        $version_numeric = ILIAS_VERSION_NUMERIC;
        return "Ihre Version $version_numeric ist nicht aktuell! Die aktuelle Version ist: $newest_version_numeric  <a style='text-decoration: none; color: lightblue;' href='$url' target='_blank'>[read more...]</a>";
    }

    public function getMailBody($newest_version_numeric, $url='#') :string
    {
        $version_numeric = ILIAS_VERSION_NUMERIC;
        return "Ihre Ilias-Version $version_numeric ist nicht aktuell! Die aktuelle Version ist: $newest_version_numeric. Mehr dazu auf: $url";
    }


    /**
     * @inheritDoc
     */
    public function createNotification(String $body): void
    {
        $il_adn_notification = new ilADNNotification();
        $il_adn_notification->setTitle($this->getNotificationTitle());
        $il_adn_notification->setBody($body);
        $il_adn_notification->setType(3);
        $il_adn_notification->setTypeDuringEvent(3);
        $il_adn_notification->setDismissable($this->getDismissable());
        $il_adn_notification->setPermanent(true);
        $il_adn_notification->setActive(true);
        $il_adn_notification->setLimitToRoles(false);
        $il_adn_notification->setEventStart(new DateTimeImmutable('now'));
        $il_adn_notification->setEventEnd(new DateTimeImmutable('now'));
        $il_adn_notification->setDisplayStart(new DateTimeImmutable('now'));
        $il_adn_notification->setDisplayEnd(new DateTimeImmutable('now'));

        $il_adn_notification->create();
    }

    public function getCurrentNotification() :array
    {
        /**
         * @var $ilDB ilDBInterface
         */
        global $ilDB;

        if (!$ilDB->tableExists('il_adn_notifications')) { throw new Exception('il_adn_notifications does not exist!'); }

        $set = $ilDB->query("SELECT count(`id`) as `entity_amount`, max(`id`) as `highest_id`, max(`create_date`) as `newest_date` FROM il_adn_notifications WHERE `title` LIKE '%Update Notification%' AND `active` = 1;");
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

    public function checkUrl($url) :array
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

    public function getNewestMayorVersion() :string
    {
        $newest_version_numeric = strval(ILIAS_VERSION_NUMERIC);
        $major = intval(explode('.', $newest_version_numeric)[0]);
        $minor = 0;
        if ($major <= 0) {
            throw new \Exception("Major version cannot be 0 or lower!");
        }
        $major = ($major + 1);

        $url = $this->settings->get('update_url', 'https://github.com/ILIAS-eLearning/ILIAS/releases/tag/v').$major.'.'.$minor;

        $result = $this->checkUrl($url);

        if ($result['status_code'] == 404) {
            return $newest_version_numeric;
        } else if ($result['content'] === false or empty($result['content'])) {
            return $newest_version_numeric;
        }
        else {
            return "$major.$minor"; // 8.0
        }
    }

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

            $url = $this->settings->get('update_url', 'https://github.com/ILIAS-eLearning/ILIAS/releases/tag/v').$major.'.'.$minor;

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



    public function sendMail(array $recipients, string $body)
    {
        /** @var ILIAS\DI\Container $DIC */
        global $DIC;
        $sender = $DIC->user()->getId();

        $mail = new ilMail($sender);

        foreach ($recipients as $recipient) {
            if(empty($recipient) OR !str_contains($recipient,'@')) {
                continue;
            }
            $mail->enqueue(
                $recipient,
                "",
                "",
                $this->getNotificationTitle(),
                $body,
                []
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

        $info = $this->getCurrentNotification();

        $version_numeric = strval(ILIAS_VERSION_NUMERIC);

        if($this->getLevel() == 'minor')
        {
            $newest_version_numeric = $this->getNewestMayorVersion();
            $newest_version_numeric = $this->getNewestMinorVersion($newest_version_numeric);
        }
        else {
            $newest_version_numeric = $this->getNewestMayorVersion();
        }

        $url = $this->settings->get('update_url', 'https://github.com/ILIAS-eLearning/ILIAS/releases/tag/v').$newest_version_numeric;

        $insistence_level = $this->getInsistenceLevel();

        if ($version_numeric != $newest_version_numeric) {

            if ($insistence_level == 'low')
            {
                ilLoggerFactory::getLogger(ilUpdateNotificationPlugin::PLUGIN_ID)->log("Ihre Version $version_numeric ist nicht aktuell! Die aktuelle Version ist: $newest_version_numeric");
                $result->setMessage("Ihre Version $version_numeric ist nicht aktuell! Die aktuelle Version ist: $newest_version_numeric");
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
                    $this->getNotificationBody($newest_version_numeric, $url)
                );
            }

            $result->setMessage('Version nicht aktuell!');
            return $result;
        }
        else {
            $this->removeNotification($info['id']);
            $result->setMessage('Version aktuell!');
            return $result;
        }
    }
}
