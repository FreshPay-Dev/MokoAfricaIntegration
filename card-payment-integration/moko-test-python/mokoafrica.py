import json
import webbrowser
import requests
import hmac
import hashlib
from datetime import UTC, datetime
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# API credentials (replace with your actual credentials)
api_key = "328fab11c5cd494eb0f80c3f7aedb67f"  # MERCHANT_API_KEY
api_secret = "29015cda152d7dc7b4042b24b7b502d92d90879f311374e87cbf24b01a2de14c"  # MERCHANT_API_SECRET
timestamp = datetime.now(UTC).isoformat().replace("+00:00", "Z")

# Payment request data
payment_data = {
    "amount": 100.50,
    "currency": "USD",
    "merchant_reference": "CMD12345",
    "bill_to_forename": "Jane",
    "bill_to_surname": "Doe",
    "bill_to_email": "jane.doe@example.com",
    "bill_to_phone": "+12125551212",
    "bill_to_address_line1": "2000 Broadway St",
    "bill_to_address_city": "New York",
    "bill_to_address_state": "NY",
    "bill_to_address_postal_code": "10023",
    "bill_to_address_country": "US",
    "callback_url": ""  # In production, set a valid callback URL
}

# Generate HMAC signature
payload = json.dumps(payment_data)
message = payload + timestamp
signature = hmac.new(api_secret.encode(), message.encode(), hashlib.sha256).hexdigest()

headers = {
    "X-API-Key": api_key,
    "X-Timestamp": timestamp,
    "X-Signature": signature,
    "Content-Type": "application/json"
}

logger.info("Initiating payment request to FreshPay API...")
try:
    response = requests.post(
        "https://test.card.gofreshpay.com/api/v1/payment/orders",
        json=payment_data,
        headers=headers,
        timeout=10  # 10 seconds timeout
    )
    
    logger.info("API Response - Status: %d", response.status_code)
    logger.debug("Response Headers: %s", response.headers)
    
    if response.ok:
        payment_response = response.json()
        logger.info("Payment initiated successfully. Transaction UUID: %s", 
                   payment_response["data"]["transaction_uuid"])
        
        redirect_url = payment_response["data"]["links"]
        logger.info("Redirecting customer to payment page: %s", redirect_url)
        webbrowser.open(redirect_url)
    else:
        logger.error("Payment initiation failed. Status: %d, Response: %s", 
                    response.status_code, response.text)
        
except requests.exceptions.RequestException as e:
    logger.error("API request failed: %s", str(e))