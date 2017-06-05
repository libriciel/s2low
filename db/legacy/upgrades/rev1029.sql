-- Création du nouvel état permettant de ne pas mettre les document 2-1 et 5-1 dans la boucle des envois d'AR
INSERT INTO actes_status (id,name) VALUES(21,'Document reçu (pas d''AR)');


-- Correction des état 7 en 21
UPDATE actes_transactions_workflow
  SET status_id=21
  FROM actes_transactions
  WHERE
        actes_transactions.id = actes_transactions_workflow.transaction_id
      AND
        status_id=7
      AND
        (
          type='2'
            OR
          type='5'
        )
      AND
        last_status_id=7
;

UPDATE actes_transactions
  SET last_status_id=21
  WHERE
    (
        type='2'
      OR
        type='5'
    )
    AND
      last_status_id=7
;
