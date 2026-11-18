# bank-lab

Laboratório de arquitetura bancária com Laravel e PostgreSQL para estudar falhas, concorrência e integridade de dados. As contas e o dinheiro são fictícios. A API é uma ferramenta local, sem autenticação ou autorização, publicada apenas em `127.0.0.1`.

Esta entrega prepara o [exercício 01](docs/exercicio-01.md). Os [demais estudos](docs/cenarios.md) exigirão evolução do ambiente. O modelo inicial guarda saldos e operações em BRL, em centavos inteiros. A sequência inicial admite falhas parciais e também deixa atomicidade, concorrência, idempotência, reconciliação e recuperação para os exercícios.

## 1. Preparar o ambiente

A base usa Laravel 13.43.0, PHP 8.4.27 com PHP-FPM, Composer 2.11.2, Nginx 1.28.3 e PostgreSQL 17.12. O `composer.lock` fixa as dependências PHP.

No Linux, instale Docker Engine com o plugin Docker Compose V2. No Windows, use Docker Desktop com integração WSL2 habilitada e execute os comandos no terminal da distribuição WSL2. Prefira manter o checkout no sistema de arquivos Linux da distribuição para que permissões e montagem funcionem como no Linux. PHP e Composer no host são dispensáveis.

Na raiz do checkout, execute em Bash:

```bash
test -f .env || cp .env.example .env
export LOCAL_UID="$(id -u)" LOCAL_GID="$(id -g)"
docker compose build app
docker compose run --rm --no-deps app composer install --no-interaction
```

Repita o `export` ao abrir outro terminal para manter as permissões dos arquivos montados. A porta padrão é 8080. Para usar outra, ajuste `APP_PORT` e `APP_URL` no `.env` e a URL dos exemplos.

## 2. Subir os serviços e preparar a base

```bash
docker compose up -d --wait
docker compose exec -T app php artisan key:generate
docker compose exec -T app php artisan migrate --seed
curl -fsS http://127.0.0.1:8080/api/health
```

Gere a chave somente na primeira preparação do `.env`. A saúde retorna HTTP 200 quando a aplicação conecta ao banco e HTTP 503 se a conexão falhar. O banco `db/bank_lab` fica na rede interna do Compose e os dados persistem no volume `experiments`.

## 3. Usar a API

```bash
base=http://127.0.0.1:8080/api
curl -fsS "$base/accounts"
curl -fsS "$base/accounts/1"
curl -fsS "$base/transfers" \
  -H 'Content-Type: application/json' \
  -d '{"source_account_id":1,"destination_account_id":2,"amount_cents":10000}'
```

Em uma base limpa, os IDs de A e B são 1 e 2. Em uma base já usada, consulte a lista e use os IDs retornados. O POST bem-sucedido responde HTTP 201 com o UUID em `data.id`. Copie esse valor ao executar a consulta:

```bash
read -r -p 'UUID da transferência: ' uuid
curl -fsS "$base/transfers/$uuid"
```

O valor deve ser um inteiro JSON entre 1 e 1.000.000 centavos, inclusive. Origem e destino devem existir e ser distintos. O serviço confere o saldo da origem na leitura inicial. Uma requisição concorrente pode usar esse mesmo saldo inicial. Entradas inválidas retornam HTTP 422 com `message` e `errors` por campo. Recursos inexistentes retornam HTTP 404 com `message`.

## 4. Rodar os testes

```bash
docker compose --profile test up -d --wait db-test
docker compose exec -T app php artisan test
```

O PHPUnit usa exclusivamente o serviço PostgreSQL `db-test` e o banco descartável `bank_lab_test`, em armazenamento temporário. A suíte prepara suas tabelas e os dados de exemplo. Mantenha a configuração do Laravel sem cache durante o laboratório.

## 5. Reproduzir a falha

O reset abaixo **apaga todas as tabelas do banco descartável de experiências `db/bank_lab`** e restaura as duas contas. O comando exige `APP_ENV=local`, o destino esperado e confirmação explícita.

```bash
docker compose exec -T app php artisan lab:reset-experiments --confirm
docker compose exec -T app php artisan lab:snapshot
docker compose exec -T -e LAB_FAILURES_ENABLED=true app php artisan lab:scenario debit-without-credit
docker compose exec -T app php artisan lab:snapshot
```

Execute o snapshot final mesmo após o código de saída 1 do cenário. A opção `-e` habilita a injeção apenas no processo Artisan do cenário. Para mantê-la desabilitada, deixe `LAB_FAILURES_ENABLED=false` no `.env` e omita a opção nas demais execuções. A API não aceita parâmetros para acioná-la.

Compare os snapshots com o [roteiro do exercício 01](docs/exercicio-01.md). O log em `storage/logs/laravel.log` registra o UUID da transferência, a etapa e o identificador da execução.

## 6. Encerrar

```bash
docker compose --profile test down
```

Esse comando preserva o volume de experiências.
