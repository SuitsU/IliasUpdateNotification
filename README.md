ILIAS Update Notification Plugin
-------------------
## Info
**Supported Languages**

German, English

(**Note** You need to set your system language to either English or German in order for this plugin to work properly.)

**Minimum ILIAS Version:**

7.0

**Maximum ILIAS Version:**

7.999

## Installation
This is a Plugin for automatic update announcements for ILIAS for the CronHook Plugin Slot.

First install and enable the [ILIAS Update Notification Plugin](https://github.com/SuitsU/IliasUpdateNotification).

Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/SuitsU/IliasUpdateNotification.git UpdateNotification
```
- Access to ILIAS and go to *Administration > Extending ILIAS > Plugins* in the Mainbar.
- Look for the Update Notification plugin in the table and hit the "Actions" dropdown and select "Install".
- When ILIAS has installed the plugin, hit the "Actions" dropdown again and select "Activate".
- Go to *Administration > System Settings and Maintenance > General Settings* in the mainbar.
- Hit the "Cron Jobs" tab.
- Look for the UpdateNotification CronJob and select "Edit".
- Schedule how often the cron-job should run (default is daily) and adjust the settings. Hit the "Save" button.
- Finished!

## Screenshots

![Notification and Settings](screenshots/updatenotification1.png)

![Email](screenshots/updatenotification2.png)

## Maintainer
[SUITS U GmbH](https://github.com/SuitsU), [support@suitsu.de](mailto:support@suitsu.de)

[Christian Pietras](https://github.com/chrisIlias1993), [christian.pietras@suitsu.de](mailto:christian.pietras@suitsu.de)