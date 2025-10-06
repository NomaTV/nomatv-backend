# Teste completo do fluxo de provedores
# Execute após fazer login no navegador

Write-Host "=== TESTE COMPLETO DO FLUXO DE PROVEDORES ==="

# Obter token do localStorage (simulado)
$token = "MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==" # Token padrão do admin

Write-Host "PASSO 1: Listando provedores existentes..."
try {
    $headers = @{ "Authorization" = "Bearer $token" }
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/provedores.php" -Headers $headers -UseBasicParsing
    $data = $response.Content | ConvertFrom-Json
    Write-Host "Total de provedores: $($data.data.Count)"
    $totalAntes = $data.data.Count
} catch {
    Write-Host "ERRO: $($_.Exception.Message)"
    exit
}

Write-Host "PASSO 2: Criando novo provedor..."
$novoProvedor = @{
    action = "criar"
    nome = "Provedor Teste NomaTV"
    dns = "http://teste.nomatv.com:8080"
    tipo = "xtream"
    ativo = $true
    id_revendedor = "1"
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/provedores.php" -Method POST -Body $novoProvedor -Headers $headers -ContentType "application/json" -UseBasicParsing
    $data = $response.Content | ConvertFrom-Json
    if ($data.success) {
        Write-Host "Provedor criado com sucesso! ID: $($data.data.id)"
        $provedorId = $data.data.id
    } else {
        Write-Host "ERRO ao criar: $($data.message)"
    }
} catch {
    Write-Host "ERRO: $($_.Exception.Message)"
}

Write-Host "PASSO 3: Verificando se foi adicionado..."
try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/provedores.php" -Headers $headers -UseBasicParsing
    $data = $response.Content | ConvertFrom-Json
    Write-Host "Total depois da criacao: $($data.data.Count)"
    if ($data.data.Count -gt $totalAntes) {
        Write-Host "OK: Provedor foi adicionado!"
    } else {
        Write-Host "ERRO: Total nao aumentou!"
    }
} catch {
    Write-Host "ERRO: $($_.Exception.Message)"
}

if ($provedorId) {
    Write-Host "PASSO 4: Excluindo provedor ID $provedorId..."
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8080/api/provedores.php?id=$provedorId" -Method DELETE -Headers $headers -UseBasicParsing
        $data = $response.Content | ConvertFrom-Json
        if ($data.success) {
            Write-Host "Provedor excluido com sucesso!"
        } else {
            Write-Host "ERRO ao excluir: $($data.message)"
        }
    } catch {
        Write-Host "ERRO: $($_.Exception.Message)"
    }

    Write-Host "PASSO 5: Verificando se foi realmente excluido..."
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8080/api/provedores.php" -Headers $headers -UseBasicParsing
        $data = $response.Content | ConvertFrom-Json
        Write-Host "Total final: $($data.data.Count)"

        $encontrado = $false
        foreach ($prov in $data.data) {
            if ($prov.id -eq $provedorId) {
                $encontrado = $true
                break
            }
        }

        if ($encontrado) {
            Write-Host "ERRO: Provedor ainda existe na lista!"
        } else {
            Write-Host "OK: Provedor foi excluido da lista!"
        }
    } catch {
        Write-Host "ERRO: $($_.Exception.Message)"
    }
}

Write-Host "=== TESTE FINALIZADO ==="