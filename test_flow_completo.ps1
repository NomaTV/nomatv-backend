# ========================================
# TESTE COMPLETO DO FLUXO DE PROVEDORES
# ========================================

Write-Host "Iniciando teste completo do sistema NomaTV..." -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost:8080"
$apiUrl = "$baseUrl/api"

# ========================================
# PASSO 1: FAZER LOGIN
# ========================================
Write-Host "PASSO 1: Fazendo login..." -ForegroundColor Yellow

$loginBody = @{
    action = "login"
    usuario = "admin"
    senha = "admin123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-WebRequest -Uri "$apiUrl/auth.php" `
        -Method POST `
        -Body $loginBody `
        -ContentType "application/json" `
        -UseBasicParsing
    
    $loginData = $loginResponse.Content | ConvertFrom-Json
    
    if ($loginData.success) {
        Write-Host "OK - Login realizado com sucesso!" -ForegroundColor Green
        Write-Host "   Usuario: $($loginData.data.usuario)" -ForegroundColor Gray
        Write-Host "   Nome: $($loginData.data.nome)" -ForegroundColor Gray
        Write-Host "   Token: $($loginData.data.token.Substring(0, 20))..." -ForegroundColor Gray
        
        $token = $loginData.data.token
    } else {
        Write-Host "ERRO - Erro no login: $($loginData.message)" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "ERRO - Erro ao fazer login: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Start-Sleep -Seconds 2

# ========================================
# PASSO 2: LISTAR PROVEDORES (ANTES)
# ========================================
Write-Host "PASSO 2: Listando provedores existentes..." -ForegroundColor Yellow

try {
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    
    $listResponse = Invoke-WebRequest -Uri "$apiUrl/provedores.php" `
        -Method GET `
        -Headers $headers `
        -UseBasicParsing
    
    $listData = $listResponse.Content | ConvertFrom-Json
    
    if ($listData.success) {
        Write-Host "OK - Provedores listados com sucesso!" -ForegroundColor Green
        Write-Host "   Total: $($listData.data.Count)" -ForegroundColor Gray
        
        if ($listData.data.Count -gt 0) {
            Write-Host "   Provedores encontrados:" -ForegroundColor Gray
            foreach ($prov in $listData.data) {
                Write-Host "   - ID: $($prov.id) | Nome: $($prov.nome) | DNS: $($prov.dns)" -ForegroundColor Gray
            }
        } else {
            Write-Host "   (Nenhum provedor cadastrado ainda)" -ForegroundColor Gray
        }
        
        $totalAntes = $listData.data.Count
    } else {
        Write-Host "ERRO - Erro ao listar provedores: $($listData.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "ERRO - Erro ao listar provedores: $_" -ForegroundColor Red
}

Write-Host ""
Start-Sleep -Seconds 2

# ========================================
# PASSO 3: CRIAR NOVO PROVEDOR
# ========================================
Write-Host "PASSO 3: Criando novo provedor..." -ForegroundColor Yellow

$novoProvedor = @{
    action = "criar"
    nome = "Provedor Teste NomaTV"
    dns = "http://teste-nomatv.com:8080"
    tipo = "xtream"
    ativo = $true
    id_revendedor = "1"
} | ConvertTo-Json

try {
    $createResponse = Invoke-WebRequest -Uri "$apiUrl/provedores.php" `
        -Method POST `
        -Body $novoProvedor `
        -Headers $headers `
        -ContentType "application/json" `
        -UseBasicParsing
    
    $createData = $createResponse.Content | ConvertFrom-Json
    
    if ($createData.success) {
        Write-Host "OK - Provedor criado com sucesso!" -ForegroundColor Green
        Write-Host "   ID: $($createData.data.id)" -ForegroundColor Gray
        Write-Host "   Nome: $($createData.data.nome)" -ForegroundColor Gray
        Write-Host "   DNS: $($createData.data.dns)" -ForegroundColor Gray
        
        $provedorId = $createData.data.id
    } else {
        Write-Host "ERRO - Erro ao criar provedor: $($createData.message)" -ForegroundColor Red
        Write-Host "   Debug: $($createResponse.Content)" -ForegroundColor DarkGray
    }
} catch {
    Write-Host "ERRO - Erro ao criar provedor: $_" -ForegroundColor Red
    Write-Host "   Detalhes: $($_.Exception.Message)" -ForegroundColor DarkGray
}

Write-Host ""
Start-Sleep -Seconds 2

# ========================================
# PASSO 4: LISTAR PROVEDORES (DEPOIS DE CRIAR)
# ========================================
Write-Host "PASSO 4: Verificando se o provedor foi adicionado..." -ForegroundColor Yellow

try {
    $listResponse2 = Invoke-WebRequest -Uri "$apiUrl/provedores.php" `
        -Method GET `
        -Headers $headers `
        -UseBasicParsing
    
    $listData2 = $listResponse2.Content | ConvertFrom-Json
    
    if ($listData2.success) {
        Write-Host "OK - Provedores listados com sucesso!" -ForegroundColor Green
        Write-Host "   Total ANTES: $totalAntes" -ForegroundColor Gray
        Write-Host "   Total DEPOIS: $($listData2.data.Count)" -ForegroundColor Gray
        
        if ($listData2.data.Count -gt $totalAntes) {
            Write-Host "   OK - Provedor foi adicionado com sucesso!" -ForegroundColor Green
        } else {
            Write-Host "   AVISO - O total nao aumentou!" -ForegroundColor Yellow
        }
        
        Write-Host "   Provedores atuais:" -ForegroundColor Gray
        foreach ($prov in $listData2.data) {
            Write-Host "   - ID: $($prov.id) | Nome: $($prov.nome) | DNS: $($prov.dns)" -ForegroundColor Gray
        }
        
        $totalDepois = $listData2.data.Count
    } else {
        Write-Host "ERRO - Erro ao listar provedores: $($listData2.message)" -ForegroundColor Red
    }
} catch {
    Write-Host "ERRO - Erro ao listar provedores: $_" -ForegroundColor Red
}

Write-Host ""
Start-Sleep -Seconds 2

# ========================================
# PASSO 5: EXCLUIR PROVEDOR
# ========================================
if ($provedorId) {
    Write-Host "PASSO 5: Excluindo o provedor criado (ID: $provedorId)..." -ForegroundColor Yellow
    
    try {
        $deleteResponse = Invoke-WebRequest -Uri "$apiUrl/provedores.php?id=$provedorId" `
            -Method DELETE `
            -Headers $headers `
            -UseBasicParsing
        
        $deleteData = $deleteResponse.Content | ConvertFrom-Json
        
        if ($deleteData.success) {
            Write-Host "OK - Provedor excluido com sucesso!" -ForegroundColor Green
        } else {
            Write-Host "ERRO - Erro ao excluir provedor: $($deleteData.message)" -ForegroundColor Red
        }
    } catch {
        Write-Host "ERRO - Erro ao excluir provedor: $_" -ForegroundColor Red
    }
    
    Write-Host ""
    Start-Sleep -Seconds 2
    
    # ========================================
    # PASSO 6: VERIFICAR SE FOI EXCLUÍDO
    # ========================================
    Write-Host "PASSO 6: Verificando se o provedor foi realmente excluido..." -ForegroundColor Yellow
    
    try {
        $listResponse3 = Invoke-WebRequest -Uri "$apiUrl/provedores.php" `
            -Method GET `
            -Headers $headers `
            -UseBasicParsing
        
        $listData3 = $listResponse3.Content | ConvertFrom-Json
        
        if ($listData3.success) {
            Write-Host "OK - Provedores listados com sucesso!" -ForegroundColor Green
            Write-Host "   Total ANTES DA EXCLUSAO: $totalDepois" -ForegroundColor Gray
            Write-Host "   Total DEPOIS DA EXCLUSAO: $($listData3.data.Count)" -ForegroundColor Gray
            
            if ($listData3.data.Count -lt $totalDepois) {
                Write-Host "   OK - Provedor foi excluido com sucesso!" -ForegroundColor Green
            } else {
                Write-Host "   AVISO - O total nao diminuiu!" -ForegroundColor Yellow
            }
            
            # Verificar se o ID específico ainda existe
            $encontrado = $false
            foreach ($prov in $listData3.data) {
                if ($prov.id -eq $provedorId) {
                    $encontrado = $true
                    break
                }
            }
            
            if ($encontrado) {
                Write-Host "   AVISO - O provedor ID $provedorId ainda existe na lista!" -ForegroundColor Yellow
            } else {
                Write-Host "   OK - Provedor ID $provedorId nao esta mais na lista!" -ForegroundColor Green
            }
            
            Write-Host "   Provedores finais:" -ForegroundColor Gray
            foreach ($prov in $listData3.data) {
                Write-Host "   - ID: $($prov.id) | Nome: $($prov.nome) | DNS: $($prov.dns)" -ForegroundColor Gray
            }
        } else {
            Write-Host "ERRO - Erro ao listar provedores: $($listData3.message)" -ForegroundColor Red
        }
    } catch {
        Write-Host "ERRO - Erro ao listar provedores: $_" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "TESTE COMPLETO FINALIZADO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
