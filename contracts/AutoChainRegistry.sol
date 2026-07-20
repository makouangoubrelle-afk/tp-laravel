// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import {AccessControl} from "@openzeppelin/contracts/access/AccessControl.sol";

/**
 * @title AutoChainRegistry
 * @notice Registre infalsifiable des événements critiques de la flotte.
 * @dev Seuls des hashes sont enregistrés : aucune donnée personnelle n'est publiée.
 */
contract AutoChainRegistry is AccessControl {
    bytes32 public constant WRITER_ROLE = keccak256("WRITER_ROLE");

    struct Record {
        bytes32 contentHash;
        bytes32 recordType;
        uint256 entityId;
        uint64 timestamp;
        address submitter;
    }

    mapping(bytes32 recordId => Record) private records;

    event RecordAnchored(
        bytes32 indexed recordId,
        bytes32 indexed contentHash,
        bytes32 indexed recordType,
        uint256 entityId,
        address submitter,
        uint256 timestamp
    );

    error RecordAlreadyExists(bytes32 recordId);
    error EmptyHash();
    error InvalidAdmin();

    constructor(address admin) {
        if (admin == address(0)) {
            revert InvalidAdmin();
        }

        _grantRole(DEFAULT_ADMIN_ROLE, admin);
        _grantRole(WRITER_ROLE, admin);
    }

    function anchorRecord(
        bytes32 recordId,
        bytes32 contentHash,
        bytes32 recordType,
        uint256 entityId
    ) external onlyRole(WRITER_ROLE) {
        if (contentHash == bytes32(0)) revert EmptyHash();
        if (records[recordId].timestamp != 0) revert RecordAlreadyExists(recordId);

        records[recordId] = Record({
            contentHash: contentHash,
            recordType: recordType,
            entityId: entityId,
            timestamp: uint64(block.timestamp),
            submitter: msg.sender
        });

        emit RecordAnchored(
            recordId,
            contentHash,
            recordType,
            entityId,
            msg.sender,
            block.timestamp
        );
    }

    function verifyRecord(bytes32 recordId, bytes32 expectedHash)
        external
        view
        returns (bool)
    {
        Record memory item = records[recordId];
        return item.timestamp != 0 && item.contentHash == expectedHash;
    }

    function getRecord(bytes32 recordId) external view returns (Record memory) {
        return records[recordId];
    }

    function recordExists(bytes32 recordId) external view returns (bool) {
        return records[recordId].timestamp != 0;
    }
}
