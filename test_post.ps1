$headers = @{
    "Authorization" = "Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA=="
    "Content-Type" = "application/json"
}

$body = '{
    "action": "criar",
    "nome": "Teste Provedor",
    "dns": "http://teste.com:8080",
    "tipo": "xtream",
    "ativo": true,
    "id_revendedor": "1"
}'

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/provedores.php" -Method POST -Headers $headers -Body $body -UseBasicParsing
    Write-Host "Status Code:" $response.StatusCode
    Write-Host "Response:" $response.Content
} catch {
    Write-Host "Error:" $_.Exception.Message
}