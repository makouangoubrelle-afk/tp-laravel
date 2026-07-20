import "dotenv/config";
import fs from "node:fs";
import path from "node:path";
import { ContractFactory, JsonRpcProvider, Wallet } from "ethers";

const root = path.resolve(import.meta.dirname, "..");
const artifactPath = path.join(root, "resources", "blockchain", "AutoChainRegistry.json");
const rpcUrl = process.env.POLYGON_RPC_URL;
const privateKey = process.env.BLOCKCHAIN_PRIVATE_KEY;

if (!rpcUrl || !privateKey) {
    console.error("POLYGON_RPC_URL et BLOCKCHAIN_PRIVATE_KEY sont obligatoires.");
    process.exit(1);
}

if (!fs.existsSync(artifactPath)) {
    console.error("Artefact absent. Lancez d'abord : npm run blockchain:compile");
    process.exit(1);
}

const artifact = JSON.parse(fs.readFileSync(artifactPath, "utf8"));
const provider = new JsonRpcProvider(rpcUrl);
const wallet = new Wallet(privateKey, provider);
const network = await provider.getNetwork();
const expectedChainId = Number(process.env.BLOCKCHAIN_CHAIN_ID || 0);
if (expectedChainId && Number(network.chainId) !== expectedChainId) {
    console.error(`Mauvais réseau : ${network.chainId}, attendu : ${expectedChainId}.`);
    process.exit(1);
}
const balance = await provider.getBalance(wallet.address);

console.log(`Déploiement depuis ${wallet.address} sur la chaîne ${network.chainId}...`);
console.log(`Solde natif : ${balance}`);

const factory = new ContractFactory(artifact.abi, artifact.bytecode, wallet);
const contract = await factory.deploy(wallet.address);
await contract.waitForDeployment();
const deployment = contract.deploymentTransaction();
const receipt = await deployment.wait();
const address = await contract.getAddress();

console.log(JSON.stringify({
    contract_address: address,
    transaction_hash: deployment.hash,
    block_number: receipt.blockNumber,
    chain_id: Number(network.chainId),
}, null, 2));
