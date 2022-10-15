Ilias Update Notification Plugin
-------------------
## Installation
This is a Plugin for automatic update announcements for Ilias for the CronHook Plugin Slot.

First install and enable the [Ilias Update Notification Plugin](https://github.com/SuitsU/IliasUpdateNotification).

Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook/UpdateNotification
cd Customizing/global/plugins/Services/Cron/CronHook/UpdateNotification
git clone https://github.com/SuitsU/IliasUpdateNotification.git
```
- Access to ILIAS and go to *Administration > Extending ILIAS > Plugins* in the Mainbar.
- Look for the Update Notification plugin in the table and hit the "Actions" dropdown and select "Install".
- When ILIAS has installed the plugin, hit the "Actions" dropdown again and select "Activate".
- Hit the "Actions" dropdown and select "Refresh Languages" to update the language files.
- Go to *Administration > System Settings and Maintenance > General Settings* in the mainbar.
- Hit the "Cron Jobs" tab.
- Look for the UpdateNotification CronJob and select "Activate".
- Look for the UpdateNotification CronJob and select "Edit".
- Schedule how often the cron-job should run (default is daily) and adjust the settings. Hit the "Save" button.
- Finished!

## Maintainer
[SUITS U GmbH](https://github.com/SuitsU), support@suitsu.de

## Contributor
Developer: [chrisIlias1993](https://github.com/chrisIlias1993)

## Info
**Supported Languages** 

German, English 

**Minimum ILIAS Version:** 

7.0 

**Maximum ILIAS Version:** 

7.999 

## TODO
- sprache ist system sprache; supported: sprachen dann nur englisch u deutsch
  - man könnte mit deepl oder community noch weitere sprachen einbinden
- mails and alle admins
- sichtbarkeit für gruppen
  - Administration > Ankündigungen
  - admins (id = 2)
  - alle oder nur admins
- plugin updates? issue #4
