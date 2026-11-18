# Exercício 01 — Débito sem crédito no destino

A sequência em [TransferService](../app/Services/TransferService.php) persiste a operação como `pending`, grava o débito, grava o crédito e marca a operação como `completed`. O cenário interrompe a execução entre as duas gravações. Uma consulta posterior mostra o débito persistido, o saldo do destino intacto e a transferência pendente.

## Garantia desejada

Uma transferência interna preserva a soma dos saldos e não pode deixar um débito sem o crédito correspondente. Uma resposta de sucesso exige a persistência completa dos dois saldos e do estado da operação.

## Reprodução

Prepare o ambiente conforme o [README](../README.md). O reset apaga todas as tabelas da base local descartável de experiências `db/bank_lab` e recria os dados de exemplo.

```bash
docker compose exec -T app php artisan lab:reset-experiments --confirm
docker compose exec -T app php artisan lab:snapshot
docker compose exec -T -e LAB_FAILURES_ENABLED=true app php artisan lab:scenario debit-without-credit
docker compose exec -T app php artisan lab:snapshot
```

O cenário informa o UUID, o identificador da execução e o ponto `after_debit`, com código de saída 1. Execute o último comando em seguida para observar os dados persistidos. Todos os valores da tabela estão em centavos.

| Momento | Conta A | Conta B | Total | Transferências |
| --- | ---: | ---: | ---: | --- |
| Antes da falha | 100000 | 50000 | 150000 | Nenhuma |
| Depois da falha | 90000 | 50000 | 140000 | Uma `pending` |

Compare o UUID com o retorno de `GET /api/transfers/{uuid}` e com os registros da etapa `after_debit` no log. A redução no total é a falha proposital do exercício.

## Evidências para conferir após sua correção

- Partindo da base limpa, uma transferência normal de 10000 centavos deve persistir saldos de 90000 e 60000, total de 150000 e uma operação `completed`.
- Com a falha após o débito, a execução deve retornar erro e uma nova consulta ao banco deve confirmar a ausência de débito isolado e a preservação do total de 150000.
- Consulte novamente os saldos e o estado persistido da operação em outra requisição ou processo. Verifique se o estado encontrado corresponde ao resultado informado e à garantia adotada para operações interrompidas.

## Perguntas

1. Quais gravações já chegaram ao banco quando a exceção é lançada?
2. Que evidência distingue um valor alterado em memória de uma alteração disponível para outro processo?
3. Que estados persistidos da operação são coerentes com a resposta ao chamador e os saldos observados?
