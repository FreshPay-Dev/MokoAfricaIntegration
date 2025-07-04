import crypto from 'crypto';
import axios from 'axios';
import open from 'open';

// Configuration
const apiKey = "328fab11c5cd494eb0f80c3f7aedb67f";
const apiSecret = "29015cda152d7dc7b4042b24b7b502d92d90879f311374e87cbf24b01a2de14c";
const timestamp = new Date().toISOString();

// Payment data
const paymentData = {
    amount: 100.50,
    currency: "USD",
    merchant_reference: `CMD-${Date.now()}`,
    bill_to_forename: "Jane",
    bill_to_surname: "Doe",
    bill_to_email: "jane.doe@example.com",
    bill_to_phone: "+12125551212",
    bill_to_address_line1: "2000 Broadway St",
    bill_to_address_city: "New York",
    bill_to_address_state: "NY",
    bill_to_address_postal_code: "10023",
    bill_to_address_country: "US",
    callback_url: "http://localhost:3000/callback"
};

// Generate HMAC signature
const payload = JSON.stringify(paymentData);
const message = payload + timestamp;
const signature = crypto.createHmac('sha256', apiSecret)
                       .update(message)
                       .digest('hex');

const headers = {
    'X-API-Key': apiKey,
    'X-Timestamp': timestamp,
    'X-Signature': signature,
    'Content-Type': 'application/json'
};

console.log("Initiating payment request to FreshPay API...");
axios.post('https://test.card.gofreshpay.com/api/v1/payment/orders', paymentData, { headers })
    .then(response => {
        console.log(`Payment initiated successfully. Transaction UUID: ${response.data.data.transaction_uuid}`);
        const redirectUrl = response.data.data.links;
        console.log(`Opening payment page: ${redirectUrl}`);
        open(redirectUrl);
    })
    .catch(error => {
        console.error("Payment initiation failed:", error.response?.data || error.message);
    });