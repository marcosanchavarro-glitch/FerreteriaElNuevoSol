# Servicios previstos

Esta carpeta define el límite de servicios de aplicación; no contiene flujos implementados en Fase 2.

- Catalog: reglas editoriales/comerciales y selección transaccional de imagen principal, cuando se desarrollen productos e imágenes.
- Orders: validación de precios y cantidades, snapshots, transacciones e idempotencia, en Fase 11.
- Integrations: adaptadores externos desacoplados, únicamente después de recibir documentación real (Fase 18).

No crear proveedores ficticios. Ningún proceso de sincronización podrá escribir campos editoriales. El contrato ExternalCatalogProvider se definirá junto con el formato normalizado real; no debe acoplar modelos al transporte de Punto de Venta.

Las escrituras de modelos no reemplazan autorización ni validación de Requests. El uso de update/insert masivos del Query Builder evita casts y eventos; no usarlo para datos comerciales o jerarquías sin validación equivalente.
