# Blockchain AutoChain Emmaus

L'application ancre uniquement des empreintes SHA-256 sur la blockchain. Les
données métier et personnelles restent dans MySQL.

## Architecture

- Contrat : `contracts/AutoChainRegistry.sol`
- Artefact ABI : `resources/blockchain/AutoChainRegistry.json`
- Pont Laravel/Polygon : `blockchain/bridge.mjs`
- Service Laravel : `app/Services/BlockchainService.php`
- Réseau de validation : Polygon Amoy (`chainId` 80002)

Le contrat utilise deux rôles OpenZeppelin :

- `DEFAULT_ADMIN_ROLE` : administre les auteurs autorisés ;
- `WRITER_ROLE` : peut ancrer un enregistrement.

Un enregistrement déjà ancré ne peut être remplacé.

## Déploiement sur Polygon Amoy

1. Créer un wallet **technique dédié** au projet. Ne jamais utiliser un wallet
   personnel et ne jamais versionner sa clé privée.
2. Obtenir du POL de test depuis le faucet officiel Polygon.
3. Renseigner temporairement `.env` :

```dotenv
BLOCKCHAIN_NETWORK=polygon-amoy
BLOCKCHAIN_CHAIN_ID=80002
POLYGON_RPC_URL=https://polygon-amoy.drpc.org
BLOCKCHAIN_PRIVATE_KEY=0xVOTRE_CLE_PRIVEE_DU_WALLET_TECHNIQUE
```

4. Compiler et déployer :

```bash
npm run blockchain:compile
npm run blockchain:deploy
```

5. Copier `contract_address` affichée dans `.env`, puis activer le mode réel :

```dotenv
BLOCKCHAIN_MODE=polygon
BLOCKCHAIN_CONTRACT_ADDRESS=0xADRESSE_DU_CONTRAT
BLOCKCHAIN_EXPLORER_URL=https://amoy.polygonscan.com
ALLOW_DEMO_WALLET=false
```

6. Vider le cache Laravel :

```bash
php artisan optimize:clear
```

## Passage sur Polygon mainnet

Après validation complète sur Amoy :

```dotenv
BLOCKCHAIN_MODE=polygon
BLOCKCHAIN_NETWORK=polygon-mainnet
BLOCKCHAIN_CHAIN_ID=137
POLYGON_RPC_URL=https://polygon-rpc.com
POLYGON_PUBLIC_RPC_URL=https://polygon-rpc.com
BLOCKCHAIN_EXPLORER_URL=https://polygonscan.com
```

Il faut redéployer le contrat sur la chaîne 137 et remplacer
`BLOCKCHAIN_CONTRACT_ADDRESS`. Le wallet technique doit alors contenir du vrai
POL pour payer les frais.

## Vérifications

```bash
npm run blockchain:test
php artisan test
```

En production, stocker `BLOCKCHAIN_PRIVATE_KEY` exclusivement dans le coffre de
secrets de l'hébergeur (Railway, Render, AWS Secrets Manager, etc.). Pour une
exploitation durable, transférer le rôle administrateur à un multisig.

## Variables obligatoires sur l'hébergeur

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.example
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
NODE_BINARY=node
ALLOW_DEMO_WALLET=false
```

L'URL publique doit utiliser HTTPS : MetaMask refusera certaines opérations sur
un domaine distant non sécurisé. La table `sessions` conserve les nonces de
signature malgré les redémarrages du conteneur.
