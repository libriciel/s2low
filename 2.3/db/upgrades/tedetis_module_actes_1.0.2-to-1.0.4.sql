SET CLIENT_ENCODING TO 'LATIN9';

ALTER TABLE actes_classification_codes ALTER COLUMN description TYPE CHARACTER VARYING(256);

ALTER TABLE authorities ALTER COLUMN broadcast_email TYPE text;
ALTER TABLE authorities ADD default_broadcast_email text;

ALTER TABLE actes_transactions ADD broadcast_emails text;
ALTER TABLE actes_transactions ADD broadcast_send_sources integer;
ALTER TABLE actes_transactions ADD broadcasted boolean DEFAULT false;
