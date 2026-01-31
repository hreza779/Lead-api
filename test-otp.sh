#!/bin/bash

# OTP Authentication Test Script
# This script tests the OTP authentication flow

BASE_URL="http://localhost:8000/api"
PHONE="09123456789"

echo "=== OTP Authentication Test ==="
echo ""

# Test 1: Send OTP
echo "1. Sending OTP to $PHONE..."
SEND_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/send-otp" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"$PHONE\"}")

echo "Response: $SEND_RESPONSE"
echo ""

# Extract OTP code from response
OTP_CODE=$(echo $SEND_RESPONSE | grep -o '"code":"[0-9]*"' | grep -o '[0-9]*')

if [ -z "$OTP_CODE" ]; then
    echo "❌ Failed to get OTP code"
    exit 1
fi

echo "✓ OTP Code received: $OTP_CODE"
echo ""

# Test 2: Verify OTP
echo "2. Verifying OTP..."
VERIFY_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/verify-otp" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"$PHONE\",\"code\":\"$OTP_CODE\"}")

echo "Response: $VERIFY_RESPONSE"
echo ""

# Extract token
TOKEN=$(echo $VERIFY_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Failed to get auth token"
    exit 1
fi

echo "✓ Token received: ${TOKEN:0:20}..."
echo ""

# Test 3: Get user info with token
echo "3. Getting user info with token..."
USER_RESPONSE=$(curl -s -X GET "$BASE_URL/user" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Response: $USER_RESPONSE"
echo ""

# Test 4: Test wrong OTP
echo "4. Testing wrong OTP code..."
WRONG_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/verify-otp" \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"$PHONE\",\"code\":\"0000\"}")

echo "Response: $WRONG_RESPONSE"
echo ""

echo "=== Test Complete ==="
