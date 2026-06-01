#!/bin/bash

# MinIO Bucket Setup Script
# Script ini membuat bucket di MinIO jika belum ada

echo "================================"
echo "MinIO Bucket Setup"
echo "================================"

MINIO_HOST="http://localhost:9000"
MINIO_USER="cloudpet_user"
MINIO_PASSWORD="cloudpet_password"
BUCKET_NAME="cloudpet-bucket"

# Check if MinIO is accessible
echo "Checking MinIO connectivity..."
curl -s -f "$MINIO_HOST/minio/health/live" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ MinIO is running at $MINIO_HOST"
else
    echo "❌ MinIO is not accessible at $MINIO_HOST"
    echo "Please start MinIO with: docker-compose up -d"
    exit 1
fi

# Check if mc (MinIO Client) is installed
if ! command -v mc &> /dev/null; then
    echo "❌ MinIO Client (mc) is not installed"
    echo "Install with: brew install minio/stable/mc (macOS) or download from https://min.io/docs/minio/linux/reference/minio-mc.html"
    exit 1
fi

echo "✅ MinIO Client found"

# Add alias
echo "Adding MinIO alias..."
mc alias set myminio "$MINIO_HOST" "$MINIO_USER" "$MINIO_PASSWORD" --api S3v4

# Check alias was added
if [ $? -eq 0 ]; then
    echo "✅ Alias added successfully"
else
    echo "⚠️  Alias may already exist, continuing..."
fi

# Create bucket
echo "Creating bucket '$BUCKET_NAME'..."
mc mb "myminio/$BUCKET_NAME" --ignore-existing

if [ $? -eq 0 ]; then
    echo "✅ Bucket created successfully"
else
    echo "❌ Failed to create bucket"
    exit 1
fi

# Set public access
echo "Setting bucket to public..."
mc anonymous set public "myminio/$BUCKET_NAME"

if [ $? -eq 0 ]; then
    echo "✅ Bucket is now public"
else
    echo "⚠️  Failed to set public access (bucket may still work)"
fi

# List contents
echo ""
echo "Bucket contents:"
mc ls "myminio/$BUCKET_NAME"

echo ""
echo "================================"
echo "✅ Setup complete!"
echo "================================"
echo ""
echo "MinIO Web Console: $MINIO_HOST/minio"
echo "Bucket: $BUCKET_NAME"
echo "Credentials: $MINIO_USER / $MINIO_PASSWORD"
