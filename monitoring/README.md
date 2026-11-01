# Monitoring Prometheus + Grafana

Cette pile ajoute Prometheus, Node Exporter, cAdvisor et Grafana au réseau Docker externe `app-network`. Elle ne modifie pas le compose Jenkins ni l'application Laravel.

## Prérequis

- Docker Desktop démarré, avec Docker Compose v2 (`docker compose`).
- Le port hôte `9090` disponible pour Prometheus, `3000` pour Grafana et `8081` pour cAdvisor. Le port `8080` reste réservé à Jenkins.
- Le réseau Docker externe `app-network`.
- Si vous souhaitez que Prometheus scrape Jenkins ou Laravel, leurs conteneurs doivent rejoindre `app-network`. Jenkins doit aussi avoir le plugin Jenkins « Prometheus metrics ».
- Un fichier `.env` dans ce dossier, contenant le mot de passe admin Grafana.

## Configuration et lancement sous Windows

Depuis la racine du dépôt, dans PowerShell :

```powershell
docker network create app-network
cd monitoring
Copy-Item .env.example .env
docker compose -f docker-compose.monitoring.yml up -d
docker compose -f docker-compose.monitoring.yml ps
```

La commande de création du réseau échoue si `app-network` existe déjà : dans ce cas, conservez le réseau existant et poursuivez avec `cd monitoring`.

Après le démarrage, ouvrez :

- Prometheus : <http://localhost:9090>
- Grafana : <http://localhost:3000> (utilisateur `admin`, mot de passe défini dans `.env`)
- cAdvisor : <http://localhost:8081>

## Vérifier les cibles Prometheus

Ouvrez <http://localhost:9090/targets>. Les cibles `prometheus`, `node-exporter` et `cadvisor` devraient être `UP`. Les cibles `jenkins` et `laravel-app` peuvent être `DOWN` jusqu'à ce que les services existent et rejoignent le réseau externe.

L'alerte `ServiceDown` exclut temporairement `laravel-app` avec le filtre `up{job!="laravel-app"}` afin d'éviter une alerte avant que Laravel n'existe. Quand Laravel sera prêt, retirez ce filtre dans `prometheus/alert.rules.yml` pour surveiller cette cible.

## Dashboards Grafana

La source Prometheus est provisionnée automatiquement. Pour importer un dashboard public, dans Grafana choisissez **Dashboards > New > Import**, puis utilisez l'identifiant :

- `1860` — Node Exporter Full.
- `14282` — cAdvisor.

Pour provisionner un dashboard depuis un fichier, exportez-le au format JSON dans Grafana avec **Share > Export > Save to file**, puis placez le JSON obtenu dans `grafana/dashboards/`. Le provider provisionné lit ce dossier au démarrage. Redémarrez Grafana après l'ajout ou la modification d'un fichier :

```powershell
docker compose -f docker-compose.monitoring.yml restart grafana
```

## Jenkins et Laravel

Le compose de monitoring rejoint uniquement le réseau externe `app-network`. Demandez à l'équipe Jenkins d'ajouter le service Jenkins et la déclaration du réseau à son compose. Jenkins doit s'appeler `jenkins-laravel` dans Docker et son plugin « Prometheus metrics » doit exposer `/prometheus`.

La cible Laravel est configurée sur `app:8000` et le chemin `/metrics`. Adaptez le nom `app` dans `prometheus/prometheus.yml` si le service porte un autre nom. Tant que l'application n'expose pas ces métriques, cette cible reste indisponible sans bloquer la pile de monitoring.

### cAdvisor sous Windows / WSL2

cAdvisor peut ne pas démarrer ou ne pas collecter toutes les métriques Docker sous Docker Desktop/WSL2, selon la configuration du socket et du système de fichiers Linux. Vérifiez que Docker Desktop utilise le backend WSL2 et que le socket et les chemins Linux (`/var/run/docker.sock`, `/sys` et `/var/lib/docker`) sont accessibles depuis les conteneurs. Si cela reste indisponible, l'alternative fiable est d'exécuter cAdvisor sur un hôte Linux/une VM Linux où ces chemins existent, puis de configurer Prometheus pour scraper l'adresse réseau de cet hôte au lieu du service `cadvisor`.

## Arrêt

Depuis le dossier `monitoring` :

```powershell
docker compose -f docker-compose.monitoring.yml down
```

Les volumes nommés `prometheus-data` et `grafana-data` sont conservés. Ne les supprimez pas si vous souhaitez garder les données et dashboards Grafana.

## Persistance des données

Les données d'exécution résident dans des volumes Docker nommés fixes, indépendants du dossier ou du nom du projet Compose. Les fichiers de configuration sous `prometheus/` et `grafana/` restent dans le dépôt et ne sont pas stockés dans ces volumes.

| Volume | Contenu conservé | Durée |
|---|---|---|
| `prometheus-data` | Base TSDB Prometheus, séries et données d'alertes | Jusqu'à suppression du volume ; rétention des séries configurée à 15 jours et taille maximale TSDB de 4 Go |
| `grafana-data` | Base de données Grafana, dashboards créés dans l'interface, utilisateurs et préférences | Jusqu'à suppression du volume |

### Commandes et effet sur les volumes

| Commande | Effet |
|---|---|
| `docker compose -f docker-compose.monitoring.yml down` | Conserve les volumes |
| `docker compose -f docker-compose.monitoring.yml restart` | Conserve les volumes |
| `docker compose -f docker-compose.monitoring.yml up -d --build` | Conserve les volumes |
| `docker compose -f docker-compose.monitoring.yml down -v` | **Détruit les volumes attachés à ce projet Compose** |
| `docker volume rm prometheus-data grafana-data` | **Détruit les volumes indiqués** |
| `docker system prune --volumes` | **Peut supprimer les volumes non utilisés** |

**NE JAMAIS lancer `docker compose down -v` ni `docker system prune --volumes` : cela supprime les données.**

### Tester la persistance Grafana

1. Démarrez la pile et ouvrez <http://localhost:3000>.
2. Créez un dashboard nommé `Test` et enregistrez-le.
3. Depuis `monitoring`, arrêtez la pile sans supprimer les volumes :

   ```powershell
   docker compose -f docker-compose.monitoring.yml down
   ```

4. Redémarrez-la :

   ```powershell
   docker compose -f docker-compose.monitoring.yml up -d
   ```

5. Rouvrez Grafana et vérifiez que le dashboard `Test` est toujours présent.

### Sauvegarder et restaurer

Depuis le dossier `monitoring`, sauvegardez les deux volumes avec des archives `.tar.gz` horodatées dans `monitoring/backups/` :

```powershell
.\backup.ps1
```

Le script accepte un dossier destination optionnel et conserve les sept sauvegardes les plus récentes de chaque volume :

```powershell
.\backup.ps1 -Destination "D:\sauvegardes\monitoring"
```

Pour restaurer, choisissez l'archive et le volume voulu. Le script vérifie l'archive, demande de saisir `RESTAURER`, arrête le service concerné, remplace le contenu du volume et rétablit les UID/GID attendus par l'image avant de relancer le service :

```powershell
.\restore.ps1 -Volume grafana-data -Archive ".\backups\grafana-20261101-1203.tar.gz"
.\restore.ps1 -Volume prometheus-data -Archive ".\backups\prometheus-20261101-1203.tar.gz"
```

La restauration écrase les données actuellement présentes dans le volume sélectionné. Faites une sauvegarde avant de restaurer si vous souhaitez pouvoir revenir à l'état actuel.

### Vérifier les volumes

Depuis PowerShell :

```powershell
docker volume ls
docker volume inspect prometheus-data
docker volume inspect grafana-data
```

## Problèmes fréquents

| Problème | Cause probable | Solution |
|---|---|---|
| Le service ne démarre pas, ou un port est déjà utilisé | Un autre processus écoute sur `9090`, `3000` ou `8081` | Libérez le port concerné ou modifiez le port hôte dans `docker-compose.monitoring.yml`. Le port hôte `8080` est réservé à Jenkins et ne doit pas être utilisé ici. |
| Compose indique que `app-network` est absent | Le réseau externe n'a pas encore été créé | Depuis la racine du dépôt, exécutez `docker network create app-network`. |
| Une cible est `DOWN` dans `/targets` | Son conteneur n'existe pas, n'a pas rejoint `app-network`, ou son endpoint/port n'est pas accessible | Vérifiez le nom du conteneur/service, son réseau et son endpoint. Jenkins requiert le plugin « Prometheus metrics » ; Laravel doit exposer `/metrics`. |
| cAdvisor ne démarre pas ou ne remonte pas de métriques | Limitation d'accès aux montages Docker Desktop/WSL2 | Vérifiez les chemins Linux et le backend WSL2, ou exécutez cAdvisor sur un hôte Linux et ajustez la cible Prometheus. |
| Grafana refuse le mot de passe ou ne démarre pas | `GRAFANA_PASSWORD` n'est pas défini ou `.env` manque | Depuis `monitoring`, créez `.env` à partir de `.env.example`, renseignez le mot de passe, puis relancez les services. |
