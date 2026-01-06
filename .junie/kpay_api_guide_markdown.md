# KPay  
## API Developer’s Guide  
**October 2025**

---

## Contents
- Get started
- Introduction
- Authentication
- Supported payment methods
- Payment request
- Check transaction status
- KPay error / return codes

---

## Get started

### Introduction

| Item | Value |
|-----|------|
| Live | `pay.esicia.rw` |
| Sandbox | `pay.esicia.com` |
| Port | 443 |
| Protocol | HTTPS |
| Authentication | Basic authorization |
| Administrator Page | TBA |

---

## Authentication

API access is granted using the **Basic Authorization HTTP Header**.

Add the `Authorization: Basic` header to your request.

### CURL Example
```bash
curl --user name:password https://pay.esicia.com/
```

The system also uses **IP whitelisting**.  
Clients must submit the list of IP addresses from which requests will originate.

Endpoint:
```
https://pay.esicia.com/
```

---

## Supported payment methods

| # | Name | pmethod value |
|---|------|---------------|
| 1 | MoMo by MTN | momo |
| 2 | Airtel Money | momo |
| 3 | Visa | cc |
| 4 | MasterCard | cc |
| 5 | Amex | cc |
| 6 | SmartCash | cc |
| 7 | SPENN | spenn |
| 8 | Safaribus | cc |

---

## Payment request

To send a payment request:

- Method: **POST**
- Format: **JSON**

### Parameters

| Parameter | Required | Type | Description |
|---------|---------|------|------------|
| action | Yes | string | `pay` or `checkstatus` |
| msisdn | Yes | string | Mobile number with country code (no `+`) |
| email | Yes | string | Payer email |
| details | Yes | string | Payment description |
| refid | Yes | string | Unique reference from your system |
| amount | Yes | integer | Amount in RWF |
| currency | No | string | Defaults to `RWF` |
| cname | Yes | string | Customer name |
| cnumber | Yes | string | Customer number at processor level |
| pmethod | Yes | string | Payment method |
| retailerid | Yes | string | Retailer ID |
| returl | Yes | string | Webhook URL |
| redirecturl | Yes | string | Redirect URL after payment |
| logourl | No | string | Logo URL for card checkout |

---

### Request body example
```json
{
  "msisdn": "0783300000",
  "details": "order",
  "refid": "15947234071471114",
  "amount": 4200,
  "currency": "RWF",
  "email": "user@user.rw",
  "cname": "CUSTOMER NAME",
  "cnumber": "123456789",
  "pmethod": "momo",
  "retailerid": "02",
  "returl": "https://iduka.rw/api/paymentack",
  "redirecturl": "https://www.iduka.rw"
}
```

---

### Response (Pending)
```json
{
  "reply": "PENDING",
  "url": "https://pay.esicia.com/checkout/A12343983489",
  "success": 1,
  "authkey": "m43snbf9oivnmersqh6mn1lbh5",
  "tid": "E6974831594723691",
  "refid": "15947234071471114",
  "retcode": 0
}
```

---

### Response (Successful – Immediate)
```json
{
  "tid": "E6974821594723662",
  "refid": "15947234071471114",
  "momtransactionid": "2785640192",
  "statusdesc": "SUCCESSFUL",
  "statusid": "01",
  "retcode": 0
}
```

---

## Test cards

### MasterCard
| Card Number | Type |
|------------|------|
| 5101 1800 0000 0007 | Commercial Credit |
| 2222 4000 7000 0005 | Commercial Debit |
| 6771 7980 2500 0004 | Mastercard |

### American Express
| Card Number | Type |
|------------|------|
| 3782 822463 10005 | Amex (CVV 4 digits) |
| 3714 496353 98431 | Amex (CVV 4 digits) |

### Visa
| Card Number | Type |
|------------|------|
| 4111 1111 1111 1111 | Consumer |
| 4444 3333 2222 1111 | Corporate |
| 4001 5900 0000 0001 | Corporate Credit |

---

## Postback (Asynchronous)

### Successful mobile money payment
```json
{
  "tid": "A441489693051",
  "refid": "1489693046",
  "momtransactionid": "616730887",
  "payaccount": "0783300000",
  "statusid": "01",
  "statusdesc": "Successfully processed transaction."
}
```

### Failed payment
```json
{
  "tid": "A2431476355795",
  "refid": "1476293424",
  "momtransactionid": "",
  "statusid": "682",
  "statusdesc": "An internal error caused the operation to fail"
}
```

---

## Check transaction status

### Request
```json
{
  "tid": "A441489693051",
  "refid": "1489693046",
  "action": "checkstatus"
}
```

### Transaction not found
```json
{
  "tid": "",
  "refid": "",
  "momtransactionid": "",
  "statusid": "611",
  "statusdesc": "Transaction not found"
}
```

---

## KPay error / return codes

| Code | Description |
|-----|------------|
| 0 | No error – processing |
| 01 | Successful payment |
| 02 | Payment failed |
| 03 | Pending transaction |
| 401 | Missing authentication header |
| 500 | Non-HTTPS request |
| 600 | Invalid username/password |
| 601 | Invalid remote user |
| 602 | IP not whitelisted |
| 603 | Missing required parameters |
| 604 | Unknown retailer |
| 605 | Retailer not enabled |
| 606 | Error processing |
| 607 | Failed mobile money transaction |
| 608 | Duplicate reference ID |
| 609 | Unknown payment method |
| 610 | Unknown or disabled financial institution |
| 611 | Transaction not found |

