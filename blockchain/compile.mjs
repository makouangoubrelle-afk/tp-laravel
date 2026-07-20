import fs from "node:fs";
import path from "node:path";
import solc from "solc";

const root = path.resolve(import.meta.dirname, "..");
const contractPath = path.join(root, "contracts", "AutoChainRegistry.sol");
const outputPath = path.join(root, "resources", "blockchain", "AutoChainRegistry.json");

function resolveImport(importPath) {
    const candidates = [
        path.join(root, importPath),
        path.join(root, "node_modules", importPath),
    ];

    for (const candidate of candidates) {
        if (fs.existsSync(candidate)) {
            return { contents: fs.readFileSync(candidate, "utf8") };
        }
    }

    return { error: `Import introuvable : ${importPath}` };
}

const input = {
    language: "Solidity",
    sources: {
        "contracts/AutoChainRegistry.sol": {
            content: fs.readFileSync(contractPath, "utf8"),
        },
    },
    settings: {
        optimizer: { enabled: true, runs: 200 },
        outputSelection: {
            "*": {
                "*": ["abi", "evm.bytecode.object", "evm.deployedBytecode.object"],
            },
        },
    },
};

const output = JSON.parse(solc.compile(JSON.stringify(input), { import: resolveImport }));
const errors = output.errors ?? [];

for (const issue of errors) {
    console.error(issue.formattedMessage);
}

if (errors.some((issue) => issue.severity === "error")) {
    process.exit(1);
}

const compiled = output.contracts["contracts/AutoChainRegistry.sol"].AutoChainRegistry;
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(
    outputPath,
    JSON.stringify(
        {
            contractName: "AutoChainRegistry",
            compiler: solc.version(),
            abi: compiled.abi,
            bytecode: `0x${compiled.evm.bytecode.object}`,
            deployedBytecode: `0x${compiled.evm.deployedBytecode.object}`,
        },
        null,
        2,
    ),
);

console.log(`Contrat compilé : ${path.relative(root, outputPath)}`);
