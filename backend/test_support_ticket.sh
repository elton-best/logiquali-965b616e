#!/bin/bash

# Test support ticket creation after fix
echo "=== Test Support Ticket Creation ==="

# Login as ClientB user
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "clientb@test.com",
    "password": "password123"
  }')

TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token // .token // empty')

if [ -z "$TOKEN" ]; then
  echo "❌ Login failed"
  echo $LOGIN_RESPONSE | jq .
  exit 1
fi

echo "✅ Login successful"

# Create support ticket
echo ""
echo "Creating support ticket..."
TICKET_RESPONSE=$(curl -s -X POST http://localhost:8000/api/v1/clientb/support/tickets \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "type": "other",
    "subject": "Test ticket après correction",
    "message": "Ceci est un test pour vérifier que le bug created_by est corrigé"
  }')

echo $TICKET_RESPONSE | jq .

# Check if success
SUCCESS=$(echo $TICKET_RESPONSE | jq -r '.success // false')
if [ "$SUCCESS" = "true" ]; then
  echo ""
  echo "✅ Support ticket created successfully!"
else
  echo ""
  echo "❌ Failed to create support ticket"
  exit 1
fi
