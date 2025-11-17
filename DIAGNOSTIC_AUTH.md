# Diagnostic - Problèmes potentiels d'authentification

## Problèmes potentiels identifiés

### 1. Configuration du firewall
**Symptôme**: Erreur lors de la connexion avec certificat
**Cause possible**: Le firewall Symfony bloque l'accès

**Vérifications**:
- Vérifier que `access_control` dans `security.yaml` autorise bien `/login` en `PUBLIC_ACCESS`
- Vérifier que le certificat est bien transmis par Apache

### 2. Services non injectés
**Symptôme**: Erreur "Cannot autowire service"
**Cause**: Les services legacy ne sont pas accessibles

**Solutions**:
- Vérifier que `HttpsConnexion`, `NounceSQL`, `UserSQL` sont bien configurés dans `services.yaml`
- Vérifier l'autowiring

### 3. Route app_home manquante
**Symptôme**: Erreur "Unable to generate a URL for the named route 'app_home'"
**Cause**: La route `app_home` est définie dans le contrôleur mais pas reconnue

**Solution**: Vérifier que le routing est bien configuré

### 4. Certificat non détecté
**Symptôme**: "Certificat SSL invalide ou manquant"
**Cause**: `HttpsConnexion::getCertificateInfo()` retourne null

**Solution**: Vérifier les variables d'environnement Apache (SSL_CLIENT_CERT, etc.)

### 5. Support du framework
**Symptôme**: Le authenticator supports() retourne false
**Cause**: La logique dans supports() bloque toutes les requêtes

**Solution**: Ajuster la logique de supports()

## Commandes de diagnostic

```bash
# Vérifier les routes
php bin/console debug:router

# Vérifier les services
php bin/console debug:container S2low

# Vérifier la configuration security
php bin/console debug:config security

# Vérifier les événements security
php bin/console debug:event security

# Clear le cache
php bin/console cache:clear
```

## Que faire ?

1. **Copier le message d'erreur exact** que vous voyez
2. **Regarder les logs** dans `/data/log/s2low.log` ou les logs Docker
3. **Tester avec curl** pour voir les headers:
   ```bash
   curl -v https://localhost:10443/
   ```
