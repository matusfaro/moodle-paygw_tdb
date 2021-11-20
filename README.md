# TDB Moodle payment gateway plugin

The plugin allows a site to connect to TDB Merchant.

This plugin was developed by [Smotana](https://smotana.com) thanks to funding from [Aspire Educational](https://aspire-educational.mn) and code was based on [WeChat](https://github.com/catalyst/moodle-paygw_wechat)'s Moodle plugin.

## Configure Moodle

- Go to site administration / Plugins / Manage payment gateways and enable the payment gateway.
- Go to site administration / Payments / Payment accounts
- Click the button 'Create payment account' then enter an account name for identifying it when setting up enrolment on payment, then save changes.
- On the Payment accounts page, click the payment gateway link to configure.
- In the configuration page, enter your appid/merchant id/key and secret from the application you have created.

## Add Enrolment on payment.

- Go to Go to Site administration > Plugins > Enrolments > Manage enrol plugins and click the eye icon opposite Enrolment on payment.
- Click the settings link, configure as required then click the 'Save changes' button.
- Go to the course you wish to enable payment for, and add the 'Enrolment on payment' enrolment method to the course.
- Select a payment account, amend the enrolment fee as necessary then click the button 'Add method'.

see also:  
[moodledocs: Payment Gateways](https://docs.moodle.org/en/Payment_gateways)  
[moodledocs: Enrolment on Payment](https://docs.moodle.org/en/Enrolment_on_payment)
