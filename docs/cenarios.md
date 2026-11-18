# Cenários de estudo

O ambiente atual prepara somente o primeiro exercício. Os demais estudos exigirão evolução do laboratório.

1. **Débito sem crédito no destino.** Investigar uma interrupção entre a persistência do débito e a do crédito.
2. **Pagamento processado em duplicidade.** Estudar o efeito de repetir a mesma solicitação de pagamento.
3. **Movimentações simultâneas acima do saldo.** Observar o resultado de solicitações simultâneas cujo total excede o saldo disponível.
4. **Acesso indevido a contas de outros clientes.** Identificar quais operações e dados devem estar acessíveis a cada cliente.
5. **Alteração de valor ou destinatário após confirmação.** Investigar divergências entre os dados confirmados e os efetivamente executados.
6. **Perda de operação já confirmada.** Verificar o que persiste quando há uma falha após a confirmação ao cliente.
7. **Divergência entre saldo e registros financeiros.** Investigar quando o saldo deixa de corresponder às movimentações registradas.
8. **Indisponibilidade em cascata.** Observar a propagação de falhas entre componentes dependentes.
9. **Rastreabilidade insuficiente ou adulterada.** Avaliar quais evidências permitem reconstruir a sequência de uma operação.
10. **Reexecução de operações após restauração e reprocessamento.** Investigar quais efeitos reaparecem ao retomar operações após uma restauração.
