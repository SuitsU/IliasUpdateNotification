Ilias Update Notification Plugin
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
This is a Plugin for automatic update announcements for Ilias for the CronHook Plugin Slot.

First install and enable the [Ilias Update Notification Plugin](https://github.com/SuitsU/IliasUpdateNotification).

Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/SuitsU/IliasUpdateNotification.git UpdateNotification
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
[SUITS U GmbH](https://github.com/SuitsU), [support@suitsu.de](mailto:support@suitsu.de)

[Christian Pietras](https://github.com/chrisIlias1993), [christian.pietras@suitsu.de](mailto:christian.pietras@suitsu.de)

## Contributor
Developer: [chrisIlias1993](https://github.com/chrisIlias1993)

## TODOs
### (geplant)
- #Administration@Roles/ "Verteiler" nutzbar?
- sprache ist system sprache; supported: sprachen dann nur englisch und deutsch
  - man könnte mit deepl oder community noch weitere sprachen einbinden
- plugin updates? issue #3
- error darstellung issue #4
