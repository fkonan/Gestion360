# Flujo Pago Cajasan (Mermaid)

```mermaid
flowchart TD
    A[Inicio de atencion en caja] --> B[Validar caja activa]
    B --> B1[Lecturas: TES_CAJATURNOS, TES_CAJAS, PER_PERSONAS]
    B1 --> C[Consultar cliente y saldo]
    C --> C1[Lecturas: PER_PERSONAS, GEN_MUNICIPIOS]
    C1 --> D{Saldo disponible?}

    D -->|No| E[Informar que no hay pago posible]
    E --> Z[Fin sin pago]

    D -->|Si| F[Crear base de operacion local]
    F --> F1[Escrituras: CON_CARGUEPAGOSYRECAUDOS, CON_DETCARGUEPAGOSYRECAUDOS estado C]
    F1 --> G[Solicitar retiro a API]
    G --> H{Retiro confirmado?}

    H -->|Si| I[Contabilizar y cerrar pago]
    I --> I1[Actualiza: CON_DETCARGUEPAGOSYRECAUDOS estado P, CON_CARGUEPAGOSYRECAUDOS estado P]
    I1 --> I2[Genera: CON_COMPROBANTES, CON_DETALLEPAGORECAUDO, CON_AUXCOMPROBANTES, TES_CAJATURNODOCUMENTOS]
    I2 --> J[Mostrar confirmacion y recibo]
    J --> Z1[Fin pago exitoso]

    H -->|No| K[Marcar pago fallido]
    K --> K1[Actualiza: CON_DETCARGUEPAGOSYRECAUDOS estado A]
    K1 --> L{Aplica reverso?}
    L -->|Si| M[Intentar reverso y auditar]
    M --> M1[Escritura: CON_REVERSO_CAJASAN]
    M1 --> N[Informar resultado al cajero]
    L -->|No| N
    N --> Z2[Fin pago fallido]
```

## Notas rapidas para explicarlo
- `estado C`: operacion creada localmente, aun pendiente de confirmacion final.
- `estado P`: pago confirmado y contabilizado localmente.
- `estado A`: pago fallido/anulado localmente.
- `CON_REVERSO_CAJASAN`: bitacora de intentos de reverso y su resultado.
