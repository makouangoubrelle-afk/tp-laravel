import fs from "node:fs";
import path from "node:path";
import {
    ContractFactory,
    JsonRpcProvider,
    Wallet,
    id,
    sha256,
    toUtf8Bytes,
    verifyMessage,
} from "ethers";

const root = path.resolve(import.meta.dirname, "..");
const artifact = JSON.parse(
    fs.readFileSync(
        path.join(root, "resources", "blockchain", "AutoChainRegistry.json"),
        "utf8",
    ),
);

const provider = new JsonRpcProvider("http://127.0.0.1:8545");
const signer = await provider.getSigner(0);
const signerAddress = await signer.getAddress();
const factory = new ContractFactory(artifact.abi, artifact.bytecode, signer);
const contract = await factory.deploy(signerAddress);
await contract.waitForDeployment();

const payload = JSON.stringify({ vehicle_id: 1, mileage: 42000 });
const contentHash = sha256(toUtf8Bytes(payload));
const transaction = await contract.anchorRecord(
    contentHash,
    contentHash,
    id("odometer"),
    1,
);
await transaction.wait();

if (!await contract.verifyRecord(contentHash, contentHash)) {
    throw new Error("La vérification du registre a échoué.");
}

const wallet = Wallet.createRandom();
const message = "AutoChain Emmaus — test signature";
const signature = await wallet.signMessage(message);
if (verifyMessage(message, signature).toLowerCase() !== wallet.address.toLowerCase()) {
    throw new Error("La récupération cryptographique du signataire a échoué.");
}

console.log(JSON.stringify({
    contract: await contract.getAddress(),
    transaction: transaction.hash,
    record_verified: true,
    signature_verified: true,
}, null, 2));
