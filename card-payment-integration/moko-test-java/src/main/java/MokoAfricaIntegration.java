import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Instant;
import java.time.format.DateTimeFormatter;
import java.util.HexFormat;
import com.google.gson.Gson;

public class MokoAfricaIntegration {
    
    private static final String API_KEY = "328fab11c5cd494eb0f80c3f7aedb67f";
    private static final String API_SECRET = "29015cda152d7dc7b4042b24b7b502d92d90879f311374e87cbf24b01a2de14c";
    private static final Gson gson = new Gson();
    
    public static void main(String[] args) throws Exception {
        // 1. Prepare request data
        String timestamp = DateTimeFormatter.ISO_INSTANT.format(Instant.now());
        
        PaymentRequest paymentData = new PaymentRequest(
            100.50,
            "USD",
            "CMD" + System.currentTimeMillis(),
            "Jane",
            "Doe",
            "jane.doe@example.com",
            "+12125551212",
            "2000 Broadway St",
            "New York",
            "NY",
            "10023",
            "US",
            ""
        );
        
        // 2. Generate JSON payload
        String payload = gson.toJson(paymentData);
        String message = payload + timestamp;
        
        // 3. Generate HMAC signature
        String signature = hmacSha256(API_SECRET, message);
        
        // 4. Send request
        HttpClient client = HttpClient.newHttpClient();
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create("https://test.card.gofreshpay.com/api/v1/payment/orders"))
                .header("X-API-Key", API_KEY)
                .header("X-Timestamp", timestamp)
                .header("X-Signature", signature)
                .header("Content-Type", "application/json")
                .POST(HttpRequest.BodyPublishers.ofString(payload))
                .build();
        
        System.out.println("=== Request Details ===");
        System.out.println("Timestamp: " + timestamp);
        System.out.println("Payload: " + payload);
        System.out.println("Signature: " + signature);
        
        // 5. Get response
        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
        
        System.out.println("\n=== Response ===");
        System.out.println("Status: " + response.statusCode());
        System.out.println("Body: " + response.body());
    }
    
    private static String hmacSha256(String secret, String message) throws Exception {
        Mac sha256_HMAC = Mac.getInstance("HmacSHA256");
        sha256_HMAC.init(new SecretKeySpec(secret.getBytes(), "HmacSHA256"));
        return HexFormat.of().formatHex(sha256_HMAC.doFinal(message.getBytes()));
    }
    
    static class PaymentRequest {
        double amount;
        String currency;
        String merchant_reference;
        String bill_to_forename;
        String bill_to_surname;
        String bill_to_email;
        String bill_to_phone;
        String bill_to_address_line1;
        String bill_to_address_city;
        String bill_to_address_state;
        String bill_to_address_postal_code;
        String bill_to_address_country;
        String callback_url;
        
        public PaymentRequest(
            double amount,
            String currency,
            String merchant_reference,
            String bill_to_forename,
            String bill_to_surname,
            String bill_to_email,
            String bill_to_phone,
            String bill_to_address_line1,
            String bill_to_address_city,
            String bill_to_address_state,
            String bill_to_address_postal_code,
            String bill_to_address_country,
            String callback_url
        ) {
            this.amount = amount;
            this.currency = currency;
            this.merchant_reference = merchant_reference;
            this.bill_to_forename = bill_to_forename;
            this.bill_to_surname = bill_to_surname;
            this.bill_to_email = bill_to_email;
            this.bill_to_phone = bill_to_phone;
            this.bill_to_address_line1 = bill_to_address_line1;
            this.bill_to_address_city = bill_to_address_city;
            this.bill_to_address_state = bill_to_address_state;
            this.bill_to_address_postal_code = bill_to_address_postal_code;
            this.bill_to_address_country = bill_to_address_country;
            this.callback_url = callback_url;
        }
    }
}