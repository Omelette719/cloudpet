# MinIO Bucket Setup Script (Windows PowerShell)
# Script ini membuat bucket di MinIO jika belum ada

Write-Host "================================" -ForegroundColor Cyan
Write-Host "MinIO Bucket Setup" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

$MINIO_HOST = "http://localhost:9000"
$MINIO_USER = "cloudpet_user"
$MINIO_PASSWORD = "cloudpet_password"
$BUCKET_NAME = "cloudpet-bucket"

# Check if MinIO is accessible
Write-Host "Checking MinIO connectivity..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "$MINIO_HOST/minio/health/live" -UseBasicParsing -ErrorAction Stop
    Write-Host "✅ MinIO is running at $MINIO_HOST" -ForegroundColor Green
} catch {
    Write-Host "❌ MinIO is not accessible at $MINIO_HOST" -ForegroundColor Red
    Write-Host "Please start MinIO with: docker-compose up -d" -ForegroundColor Yellow
    exit 1
}

# Check if mc (MinIO Client) is installed
Write-Host "Checking MinIO Client (mc)..." -ForegroundColor Yellow
try {
    $mc_version = mc --version 2>$null
    Write-Host "✅ MinIO Client found: $mc_version" -ForegroundColor Green
} catch {
    Write-Host "❌ MinIO Client (mc) is not installed" -ForegroundColor Red
    Write-Host "Download from: https://dl.min.io/client/mc/release/windows-amd64/mc.exe" -ForegroundColor Yellow
    exit 1
}

# Add alias
Write-Host "Adding MinIO alias..." -ForegroundColor Yellow
mc alias set myminio "$MINIO_HOST" "$MINIO_USER" "$MINIO_PASSWORD" --api S3v4

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Alias added successfully" -ForegroundColor Green
} else {
    Write-Host "⚠️  Alias may already exist, continuing..." -ForegroundColor Yellow
}

# Create bucket
Write-Host "Creating bucket '$BUCKET_NAME'..." -ForegroundColor Yellow
mc mb "myminio/$BUCKET_NAME" --ignore-existing

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Bucket created successfully" -ForegroundColor Green
} else {
    Write-Host "❌ Failed to create bucket" -ForegroundColor Red
    exit 1
}

# Set public access
Write-Host "Setting bucket to public..." -ForegroundColor Yellow
mc anonymous set public "myminio/$BUCKET_NAME"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Bucket is now public" -ForegroundColor Green
} else {
    Write-Host "⚠️  Failed to set public access (bucket may still work)" -ForegroundColor Yellow
}

# List contents
Write-Host ""
Write-Host "Bucket contents:" -ForegroundColor Cyan
mc ls "myminio/$BUCKET_NAME"

Write-Host ""
Write-Host "================================" -ForegroundColor Green
Write-Host "✅ Setup complete!" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green
Write-Host ""
Write-Host "MinIO Web Console: $MINIO_HOST/minio" -ForegroundColor Cyan
Write-Host "Bucket: $BUCKET_NAME" -ForegroundColor Cyan
Write-Host "Credentials: $MINIO_USER / $MINIO_PASSWORD" -ForegroundColor Cyan
