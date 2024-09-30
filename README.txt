=== BulletProof Payment Gateway ===
Contributors: bulletproofcheckout
Tags: woocommerce, 3DS, payment gateway, bulletproof, NMI
Requires at least: 4.0
Tested up to: 6.5.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Receive Credit Card payments using the BulletProof Gateway

== Description ==

BulletProof Checkout Gateway seamlessly integrates 3DS chargeback prevention technology with WooCommerce to accept secure credit card payments and prevent chargebacks. Our payment gateway allows customers to make safe and secure payments using their credit cards. BulletProof provides merchants with the highest level of 3DS chargeback prevention and fraud protection tools in the marketplace. 

"Say goodbye to fraudulent chargebacks and the headaches they cause!"


#### BACKGROUND 
Years ago, credit card brands developed a technology called 3DS to prevent fraud chargebacks. When used correctly, Cardbrands will assume liability for fraud chargebacks, so you don't have to deal with them.

BulletProof Checkout has leveraged 3DS technology to create a user-friendly platform that is easy to implement and customize according to your needs.

"Our advanced technology can protect your revenue and prevent the closure of your merchant account, providing peace of mind." 


== Third-Party Service Dependency ==

This plugin relies on the use of a third-party service under certain circumstances. Below are the details:

### Bulletproof Checkout API
- **Service Description**: Bulletproof Checkout is a payment processing platform that allows you to accept payments online.
- **When Used**: This service is used to process payments whenever a user makes a purchase through your website using our plugin.
- **Link to Service**: [Bulletproof Checkout](https://bulletproof-checkout.com/)
- **Terms of Use**: [Bulletproof Checkout Terms of Use](https://bulletproof-checkout.com/privacy-policy/)
- **Privacy Policy**: [Bulletproof Checkout Privacy Policy](https://bulletproof-checkout.com/privacy-policy/)

The plugin interacts with the following API endpoints:
- **Direct Post Processors**: This endpoint retrieves available payment processors.
  - URL: [https://bulletproofcheckout.net/API/endpoints/directpost/processors.php](https://bulletproofcheckout.net/API/endpoints/directpost/processors.php)
- **Capture Payment**: This endpoint captures a previously authorized payment.
  - URL: [https://bulletproofcheckout.net/API/endpoints/directpost/capture_payment.php](https://bulletproofcheckout.net/API/endpoints/directpost/capture_payment.php)
- **Validate Payment**: This endpoint validates payment details and tokens.
  - URL: [https://bulletproofcheckout.net/API/endpoints/directpost/validate.php](https://bulletproofcheckout.net/API/endpoints/directpost/validate.php)
- **Refund Payment**: This endpoint processes refunds for transactions.
  - URL: [https://bulletproofcheckout.net/API/endpoints/directpost/refund.php](https://bulletproofcheckout.net/API/endpoints/directpost/refund.php)


#### OFFERING
The BulletProof Checkout gateway is compatible many top credit card processors.  If you currently have a merchant account with a compatible processor or are looking for an integrated processing solution, we can help. Contact us with any questions.  

- Payment Agnostic - we integrate with many processors, allowing you to use your current merchant account and help you connect it into our BuletProof Gateway. 

- Multi Gateway Compatible -- We are also compatible with the NMI gateway.


#### 3DS PAYMENT GATEWAY FEATURES
- Virtual Terminal, Invoicing, QR codes, Payment Links, Payment Forms, and Payment Buttons.
- Save on the cost of accepting credit cards by utilizing our Savings Optimizer."
- Achieve more savings by using our 3DS and Savings Optimizer technology stack together.
- Credit card fee deflection programs available.
- Four plans to choose from - Gold, Platinum, Custom 3DS, Dynamic 3DS
- Full reporting through a dedicated portal.

#### THE BULLETPROOF 3DS DIFFERENCE
If your experience using 3DS did not meet your expectations, it's time to try the BulletProof 3DS difference. 
- Control the customer checkout experience.
- 3DS Settings that allow a truly frictionless, intuitive, and lightning-fast customer checkout.
- Lock in the level or BulletProof 3DS protection to secure against chargebacks.
- User-friendly 3DS solution that requires no programming skills and is easy to set up. 
- Understand 3DS transaction results through detailed reporting.
- Responsive tech support and assistance for our plugin and gateway.
- Synergistically works with RDR, Ethica, and Verifi alerts.


#### PAYMENT ACCEPTANCE METHODS 
- Credit Card
- Checks
- Cash 
- More to come

#### PRO-ACTIVE PREVENTION BENEFITS 
Preventing a chargeback avoids the following:
-  Chargeback Fees
- Additional Processing and Gateway Fees 
- Loss of Revenue
- Dispute and Arbitration Fees
- Possible Loss of Tangible Goods
- Loss of Time  
- Headaches
- "Possible Closure of Merchant Account and Inability to Accept Credit Card Payments"

== Features ==

- Seamless integration with WooCommerce.
- Secure payments using the BulletProof Checkout.
- Amazing 3DS chargeback prevention, adjustable 3DS settings, and take control over the customer checkout experience.
- Sale, authorization, subscription, and order processing.
- Collect payments inside and outside of WooCommerce.
- Refund via Dashboard: Process full or partial refunds, directly from your WordPress dashboard. 


== Plus Version ==
### WooCommerce Plugin Plus Version. (Free for any already existing customer)
- Integrated 3DS controls within your WooCommerce store.
- Enhanced secure payment page that ensures all your transactions are safe, secure and encrypted with no programming required. 
- Adds an additional layer of security to SSL with point-to-point encryption for enhanced protection.
- WooCommerce Order status updates received automatically from the gateway via webhooks.
- Authorize can capture later direct from the WooCommerce order list
- Secure "Thank you" page integrated with your WooComemrce store
- The payment page offers the highest level of security, preventing system administrators and third parties from altering or injecting malicious code.



== Installation ==

1. Upload the `bulletproof-payment-gateway` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to WooCommerce > Settings > Payments and configure your BulletProof Payment Gateway settings.

== Configuration ==

1. Visit the WooCommerce settings page and click on the "Payments" tab.
2. Find "BulletProof Payment Gateway" in the list of available payment methods and click "Manage."
3. Configure the necessary settings, such as API key, user name and password .
4. The Customer Vault feature is only available if you have enabled the Customer Vault feature in your BulletProof Account, otherwise will trigger an error in the checkout. During the initial tests set to "No"
5. Select your processor, if you leave the processor dropdon unselected then will trigger an error in the checkout page.
6. Save changes.

== Usage ==

1. During the checkout process, customers will see credit card as a payment option.
2. Customers provide necessary payment details and complete the order.
3. The plugin processes the payment through the BulletProof API.
4. Order status is updated based on the BulletProof API response.
5. Refunds and Voids are only available in the BulletProof portal, any refund or void at the WooCommerce level will not trigger the action in the gateway (this is available automated in the plugin PLUS version)
6. Any change in the payment at the BulletProof portal (ex. a refund) will not be reflected in the order status on WooCommerce (this is available automated in the plugin PLUS version)

== Frequently Asked Questions ==

### What is the distinction between Chargeback Prevention and Chargeback Protection? 
- "Chargeback prevention" refers to a proactive strategy to avoid receiving a chargeback and the associated difficulties of disputing and winning it. The use of 3DS provides a proactive, preventative approach to avoid fraud-based chargebacks and prevent them from being reported to your merchant account.

"Chargeback protection" includes a range of services and tools that help you deal with an actual chargeback reported to your merchant account and achieve the best possible outcome.


###What if we already have a company that is managing our chargebacks?
- 3DS provides a proactive preventative approach so that you can avoid fraud-based chargebacks and save time and money by not receiving them. 

You can use our 3DS chargeback prevention solution synergistically alongside any other chargeback protection solution to address chargebacks outside the scope of 3DS

We also offer chargeback protection services that are integrated into our payment gateway.


### Why is BulletProof Checkout the best option for 3DS?
- We are the world’s first fully featured 3DS gateway. All of our payment methods boast easy-to-use 3DS technology without the need for any programming. 

We have addressed the most common reasons why merchants have yet to entirely embrace 3DS technology at the checkout and provided a means to control the 3DS checkout experience.


### Can I use BulletProof Checkout payment gateway alongside my current processor?
- Yes, as long as we are compatible.  

We can also provide a cost-effective merchant account and integrate it into BulletProof without any effort on your end.

Contact info@bulletproof-checkout.com

### Countries Served
- Currently we support US, CA, and UK. We receive Credit Cards Worlwide.


### What are the requirements or paperwork needed?
- Contact us to activate for the any and all of the following

To activate the payment gateway contact docs@bulletproof-checkout.com

To apply for a merchant account contact docs@bulletproof-checkout.com

For any technical support or integration questions contact support@bulletproof-checkout.com


### How can I protect against orders where the billing and shipping don’t match?
- We offer a switch that allows you to decide whether to allow orders to only process if the billing and shipping address match.


### What are the max transaction limit size that will be 3DS protected?
- There is no max transaction limit that can be 3DS protected.


### Where can I get API credentials? 
- You need to sign up for an account with BulletProof and obtain API credentials.  Contact docs@bulletproof-checkout.com 

### Compatibility issues 
- If you have enabled JetPack and will use the WooCommerce Mobile App you will need to disable the JetPack notifications in the page wp-admin/admin.php?page=jetpack_modules

== Changelog ==

= 1.0.0 =
Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of BulletProof Payment Gateway.
