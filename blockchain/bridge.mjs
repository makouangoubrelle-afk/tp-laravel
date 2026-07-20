import "dotenv/config";
import fs from "node:fs";
import path from "node:path";
import {
    Contract,
    JsonRpcProvider,
    Wallet,
    id,
    verifyMessage,
} from "ethers";

const root = path.resolve(import.meta.dirname, "..");
const artifactPath = path.join(root, "resources", "blockchain", "AutoChainRegistry.json");

function respond(payload, exitCode = 0) {
    process.stdout.write(JSON.stringify(payload));
    process.exit(exitCode);
}

async function readInput() {
    let body = "";
    for await (const chunk of process.stdin) body += chunk;
    return JSON.parse(body || "{}");
}

function bytes32(value, field) {
    const normalized = value.startsWith("0x") ? value : `0x${value}`;
    if (!/^0x[0-9a-fA-F]{64}$/.test(normalized)) {
        throw new Error(`${field} doit contenir exactement 32 octets.`);
    }
    return normalized;
}

try {
    const input = await readInput();

    if (input.action === "recover") {
        respond({ address: verifyMessage(input.message, input.signature) });
    }

    if (!fs.existsSync(artifactPath)) {
        throw new Error("Artefact du contrat absent.");
    }

    const rpcUrl = process.env.POLYGON_RPC_URL;
    const contractAddress = process.env.BLOCKCHAIN_CONTRACT_ADDRESS;
    if (!rpcUrl || !contractAddress) {
        throw new Error("Configuration Polygon incomplète.");
    }

    const artifact = JSON.parse(fs.readFileSync(artifactPath, "utf8"));
    const provider = new JsonRpcProvider(rpcUrl);
    const network = await provider.getNetwork();
    const expectedChainId = Number(process.env.BLOCKCHAIN_CHAIN_ID || 0);
    if (expectedChainId && Number(network.chainId) !== expectedChainId) {
        throw new Error(
            `Mauvais réseau RPC : chaîne ${network.chainId}, chaîne attendue ${expectedChainId}.`,
        );
    }

    if (input.action === "status") {
        const code = await provider.getCode(contractAddress);
        respond({
            connected: true,
            chain_id: Number(network.chainId),
            contract_deployed: code !== "0x",
            block_number: await provider.getBlockNumber(),
        });
    }

    const readContract = new Contract(contractAddress, artifact.abi, provider);
    const recordId = bytes32(input.record_id, "record_id");
    const contentHash = bytes32(input.content_hash, "content_hash");

    if (input.action === "verify") {
        const valid = await readContract.verifyRecord(recordId, contentHash);
        const record = await readContract.getRecord(recordId);
        respond({
            valid,
            timestamp: Number(record.timestamp),
            submitter: record.submitter,
            entity_id: record.entityId.toString(),
        });
    }

    if (input.action === "record") {
        const privateKey = process.env.BLOCKCHAIN_PRIVATE_KEY;
        if (!privateKey) throw new Error("BLOCKCHAIN_PRIVATE_KEY est absente.");

        const exists = await readContract.recordExists(recordId);
        if (exists) {
            const valid = await readContract.verifyRecord(recordId, contentHash);
            respond({ already_recorded: true, valid });
        }

        const wallet = new Wallet(privateKey, provider);
        const contract = readContract.connect(wallet);
        const transaction = await contract.anchorRecord(
            recordId,
            contentHash,
            id(input.record_type || "generic"),
            BigInt(input.entity_id || 0),
        );
        const confirmations = Number(process.env.BLOCKCHAIN_CONFIRMATIONS || 1);
        const receipt = await transaction.wait(confirmations);

        respond({
            already_recorded: false,
            transaction_hash: transaction.hash,
            block_number: receipt.blockNumber,
            chain_id: Number(network.chainId),
            submitter: wallet.address,
        });
    }

    throw new Error(`Action inconnue : ${input.action}`);
} catch (error) {
    respond({ error: error.shortMessage || error.message || String(error) }, 1);
}
