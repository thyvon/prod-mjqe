Payload when create

{
  "date": "2025-01-14",
  "user_id": 16,
  "supplier_id": 1,
  "status": "Pending",
  "currency": "USD",
  "currency_rate": 110,
  "payment_term": "Credit 1 Month",
  "purpose": "DD",
  "vat": 10,
  "items": [
      {
          "pr_item_id": 12,
          "pr_number": "PR-CEN-20250113-001",
          "sku": "PROD-000001",
          "description": "Testing",
          "pr_description": "New iPhone 15 Pro Max 256GB LLA",
          "concatenated_description": "New iPhone 15 Pro Max 256GB LLA | Testing",
          "qty": 1,
          "uom": "UNIT",
          "unit_price": 1000,
          "total_price": 1089,
          "campus": "CEN",
          "division": "OTHER",
          "department": "PROD",
          "location": "DD",
          "total_usd": 990,
          "total_khr": 3960000,
          "purchaser_id": 16,
          "discount": 10,
          "vat": 0,
          "id": null
      }
  ],
  "id": null,
  "po_number": "",
  "total_amount_usd": 1089,
  "supplier_phone": "096 36 12 146",
  "supplier_address": "Phnom Penh",
  "total_item": 1
}



Payload when update

{
  "date": "2025-01-13",
  "user_id": 16,
  "supplier_id": 1,
  "status": "Pending",
  "currency": "USD",
  "currency_rate": 4100,
  "payment_term": "Credit 1 Month",
  "purpose": "For testing PO",
  "vat": 10,
  "items": [
      {
          "id": 141,
          "pr_item_id": 12,
          "description": "HelloDD",
          "pr_description": "New iPhone 15 Pro Max 256GB LLA",
          "concatenated_description": "New iPhone 15 Pro Max 256GB LLA | HelloDD",
          "qty": 1,
          "uom": "UNIT",
          "unit_price": 1000,
          "total_price": 550,
          "pr_number": "PR-CEN-20250113-001",
          "sku": "PROD-000001",
          "campus": "CEN",
          "division": "OTHER",
          "department": "PROD",
          "location": "Location",
          "total_usd": 550,
          "total_khr": 2200000,
          "purchaser_id": 16,
          "discount": 500,
          "vat": 10
      }
  ],
  "id": 7,
  "po_number": "PO-2025-01-00006",
  "total_amount_usd": 550,
  "supplier_phone": "096 36 12 146",
  "supplier_address": "Phnom Penh",
  "total_item": 1
}