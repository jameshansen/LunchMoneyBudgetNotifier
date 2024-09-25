# Lunch Money Budget Notifier

<img src="https://github.com/user-attachments/assets/946d4130-f43b-46eb-84d6-5d86575d894d" alt="Lunch Money Budget Notifier" width="50%">

## Description

This PHP script is designed to monitor and track monthly budgets using [Lunch Money](https://lunchmoney.app/) and send notifications using [PushOver](https://pushover.net/).

It fetches data from an API, calculates the remaining budget, checks for pending imports, and sends notifications via Pushover when changes occur.

The tool is ideal for families who need to keep a close eye on their monthly spending and adhere to a monthly budget.

## Preparation for use in Real Life

This tool is created with the expectation that all the accounts in your Lunch Money are just used for discretionary spending, like groceries, restaurants and gas.

Before using this tool, create a spreadsheet for all your regular bills vs all your income, and calculating how much spending money you have left per month. This is the figure you can then input into the SPENDING_BUDGET value in the configuration.

All the spending accounts should be zero'd (paid off) by the 1st of each month. It helps to use a credit card that has a payment date near the beginning of the month.

## Lunch Money and PushOver Account Setup

### Lunch Money
In your Lunch Money account, click the Gear Icon and then the **Developers** tab and then **Request Access Token**

<kbd><img src="https://github.com/user-attachments/assets/a67dc63c-8487-408a-83d5-846decf541c8" alt="Lunch Money Access Token" width="50%"></kbd>

Copy and paste the Site Token key it generates into a text file for safe keeping.

### PushOver
On PushOver the User Key is visible when you log in.

<kbd><img src="https://github.com/user-attachments/assets/17d17f5d-1c9d-4d08-a41c-ec7585309a9d" alt="PushOver User Key" width="50%"></kbd>

However, you need to create an application, in this case I called mine **Balance Update**

<kbd><img src="https://github.com/user-attachments/assets/d0875b49-ca3e-4bd2-a647-01ae9cb13465" alt="PushOver Application Creation" width="50%"></kbd>

And on the information screen for the application you will get the **App Token**

<kbd><img src="https://github.com/user-attachments/assets/76e714c0-311f-40b3-985e-f4349fa93ac9" alt="PushOver App Token" width="50%"></kbd>

## Configuration

Open the lunch_money_notifier_config.php file and modify the following constants at the top of the file based on the keys retrieved:

```php
define('SITE_TOKEN', 'your_site_token_here');
define('PUSHOVER_USER_KEY', 'your_pushover_user_key_here');
define('PUSHOVER_APP_TOKEN', 'your_pushover_app_token_here');
define('SPENDING_BUDGET', 3000); // Change this to your monthly spending budget in dollars
define('CHECK_INTERVAL', 300); // 5 minutes in seconds
```

Set your Spending Budget to the amount of discretionary spending you have available.

## Running the Script
Now your script is configured, you just have to run it. It is PHP for maximum compatibility with most web hosting with shell access, but you can also run it on your local system.

### Linux Server (recommended)
1. On your linux server, upload the files to a new **/etc/lunchmoney** folder.
2. Login to the shell and set the folder permissions to 777: **chmod 777 -R /etc/lunchmoney**
3. Start the script in a **screen** instance: **screen php /etc/lunchmoney/lunch_money_notifier.php**
4. Press **CTRL+A** and **D** to let the script continue to run in the background
5. To reconnect to the session, login to the shell and type **screen -r**

### Windows
1. Install [XAMPP](https://www.apachefriends.org/)
2. At the command line, run **C:\xampp\php\php lunch_money_notifier.php**

## Plaid Account Notes
Plaid is the service which Lunch Money uses to update its account information. This script will send a Plaid Account update request every interval if there is not one in progress.

Plaid however is sometimes temperamental and may not always update quickly or at all.

## Requirements

- PHP 7.0 or higher
- cURL extension for PHP
- Write permissions in the script's directory
- Lunch Money account and API credentials
- PushOver account and API credentials

## Troubleshooting

- Ensure all API endpoints are accessible and returning expected data.
- Check that you have the correct Pushover credentials.
- Verify that the script has write permissions in its directory for the local data file.
- If notifications aren't being received, check your Pushover settings and device setup.

## Contributing

Contributions, issues, and feature requests are welcome. Feel free to check [issues page](link_to_your_issues_page) if you want to contribute.

## License

[MIT](https://choosealicense.com/licenses/mit/)

## Author

James Hansen

## Acknowledgments

- @juftin's cool [lunchable-pushlunch](https://github.com/juftin/lunchable-pushlunch) project which was the inspiration for this one.
- Lunch Money for being an awesome service.
- Pushover for providing the notification service that is super easy to use.
