$body = @{ email = 'admin@falconerp.com'; password = 'password' } | ConvertTo-Json
$loginRes = Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/login' -Method Post -Body $body -ContentType 'application/json' -Headers @{ 'Accept' = 'application/json' }
$token = $loginRes.token
Write-Host "Logged in as $($loginRes.user.name). Token: $($token.Substring(0, 15))..."

$headers = @{
    'Authorization' = "Bearer $token"
    'Accept' = 'application/json'
    'X-Company-ID' = '1'
}

$endpoints = @(
    '/api/sales/dashboard',
    '/api/sales/customers',
    '/api/sales/orders',
    '/api/sales/invoices',
    '/api/sales/quotes',
    '/api/sales/credit-notes',
    '/api/sales/cash-sales',
    '/api/sales/refunds',
    '/api/sales/delivery-notes',
    '/api/sales/analytics',
    '/api/purchase/dashboard',
    '/api/purchase/vendors',
    '/api/purchase/orders',
    '/api/purchase/purchases',
    '/api/purchase/returns',
    '/api/purchase/analytics'
)

foreach ($ep in $endpoints) {
    try {
        $res = Invoke-RestMethod -Uri "http://127.0.0.1:8000$ep" -Method Get -Headers $headers
        $count = 0
        if ($res.data -is [System.Array]) { $count = $res.data.Count }
        elseif ($res.data.data -is [System.Array]) { $count = $res.data.data.Count }
        Write-Host "$ep -> HTTP 200 OK (Items/Records: $count)"
    } catch {
        Write-Host "$ep -> FAILED: $($_.Exception.Message)"
    }
}
