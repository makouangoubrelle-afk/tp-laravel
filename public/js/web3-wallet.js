/**
 * AutoChain Emmaus — Connexion MetaMask (ethers.js)
 */
class AutoChainWallet {
    constructor() {
        this.csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        this.statusEl = null;
        this.isConnecting = false;
    }

    setStatus(html, type = 'info') {
        if (!this.statusEl) return;
        const colors = { info: '#74b9ff', success: '#52b788', error: '#ff8fab', warning: '#ffd166' };
        this.statusEl.innerHTML = html;
        this.statusEl.style.borderColor = colors[type] || colors.info;
        this.statusEl.style.display = 'block';
    }

    bindStatus(el) {
        this.statusEl = el;
    }

    hasMetaMask() {
        return typeof window.ethereum !== 'undefined';
    }

    errorCode(error) {
        return error?.code
            ?? error?.error?.code
            ?? error?.info?.error?.code
            ?? error?.data?.originalError?.code
            ?? error?.cause?.code;
    }

    errorText(error) {
        const parts = [
            error?.message,
            error?.shortMessage,
            error?.reason,
            error?.error?.message,
            error?.info?.error?.message,
            error?.data?.originalError?.message,
            error?.cause?.message,
        ].filter(Boolean);

        try {
            parts.push(JSON.stringify(error?.info ?? error?.error ?? error?.data ?? {}));
        } catch (_) {
            // Certains objets MetaMask contiennent des références circulaires.
        }

        return parts.join(' ').toLowerCase();
    }

    isPendingRequest(error) {
        const text = this.errorText(error);
        return this.errorCode(error) === -32002
            || text.includes('-32002')
            || text.includes('already pending')
            || text.includes('request already pending');
    }

    isUnknownChain(error) {
        return this.errorCode(error) === 4902 || this.errorText(error).includes('4902');
    }

    isRejectedRequest(error) {
        const text = this.errorText(error);
        return this.errorCode(error) === 4001
            || text.includes('4001')
            || text.includes('user rejected')
            || text.includes('user denied');
    }

    async waitForPendingConnection(timeoutMs = 90000) {
        this.setStatus(
            '⏳ Une demande est déjà ouverte dans MetaMask. Ouvrez l’extension, puis cliquez sur <strong>Connecter</strong>. L’application attend votre validation…',
            'warning'
        );

        const deadline = Date.now() + timeoutMs;
        while (Date.now() < deadline) {
            const accounts = await window.ethereum.request({ method: 'eth_accounts' });
            if (accounts?.[0]) return accounts;
            await new Promise(resolve => setTimeout(resolve, 1200));
        }

        throw new Error(
            'La demande MetaMask est toujours bloquée. Ouvrez MetaMask → validez ou refusez la demande en attente, puis rechargez cette page.'
        );
    }

    async ensurePolygonNetwork() {
        const config = window.autoChainConfig;
        if (!config?.chainId) return;

        const chainId = `0x${Number(config.chainId).toString(16)}`;
        const networkParams = {
            chainId,
            chainName: config.chainName,
            nativeCurrency: config.nativeCurrency,
            rpcUrls: [config.rpcUrl],
            blockExplorerUrls: [config.explorerUrl],
        };
        const current = await window.ethereum.request({ method: 'eth_chainId' });
        if (current.toLowerCase() === chainId.toLowerCase()) {
            try {
                await window.ethereum.request({ method: 'eth_blockNumber' });
                return;
            } catch (_) {
                this.setStatus('⏳ Mise à jour du serveur RPC Polygon Amoy dans MetaMask…', 'warning');
                await window.ethereum.request({
                    method: 'wallet_addEthereumChain',
                    params: [networkParams],
                });
                return;
            }
        }

        try {
            await window.ethereum.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId }],
            });
        } catch (error) {
            if (!this.isUnknownChain(error)) throw error;

            await window.ethereum.request({
                method: 'wallet_addEthereumChain',
                params: [networkParams],
            });
        }
    }

    metaMaskError(error) {
        if (this.isRejectedRequest(error)) {
            return 'Connexion refusée. Dans MetaMask, cliquez sur <strong>Connecter</strong> (ou <strong>Next</strong>) pour autoriser le site.';
        }
        if (this.isPendingRequest(error)) {
            return 'Une demande MetaMask est déjà en cours. Ouvrez l’extension MetaMask, validez ou refusez la demande en attente, puis réessayez.';
        }
        return error?.shortMessage || error?.reason || error?.message || 'Erreur MetaMask inconnue.';
    }

    async connect() {
        if (!this.hasMetaMask()) {
            throw new Error('MetaMask non installé. Téléchargez-le sur metamask.io ou utilisez « Activer session démo ».');
        }

        // Étape 1 : autoriser le site dans MetaMask
        this.setStatus('⏳ <strong>Étape 1/2</strong> — MetaMask va s\'ouvrir. Cliquez sur <strong>Connecter</strong> pour autoriser ce site.', 'warning');

        const provider = new ethers.BrowserProvider(window.ethereum);
        let accounts;
        try {
            accounts = await provider.send('eth_accounts', []);
            if (!accounts?.[0]) {
                try {
                    accounts = await provider.send('eth_requestAccounts', []);
                } catch (error) {
                    if (!this.isPendingRequest(error)) throw error;
                    accounts = await this.waitForPendingConnection();
                }
            }
        } catch (e) {
            throw new Error(this.metaMaskError(e));
        }

        if (!accounts || !accounts[0]) {
            throw new Error('Aucun compte MetaMask sélectionné.');
        }

        const signer = await provider.getSigner(accounts[0]);
        const address = await signer.getAddress();

        this.setStatus(`✓ Site connecté : <code>${address.slice(0, 10)}...${address.slice(-4)}</code><br>⏳ <strong>Étape 2/2</strong> — Cliquez sur <strong>Signer</strong> dans MetaMask.`, 'warning');

        // Récupérer le message à signer
        const nonceRes = await fetch(`/wallet/nonce?ts=${Date.now()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!nonceRes.ok) {
            throw new Error('Session expirée. Reconnectez-vous au site Laravel puis réessayez.');
        }

        const { message, nonce } = await nonceRes.json();

        // Étape 2 : signature
        let signature;
        try {
            signature = await signer.signMessage(message);
        } catch (e) {
            throw new Error(this.metaMaskError(e).replace('Connecter', 'Signer'));
        }

        this.setStatus('⏳ Vérification de la signature...', 'info');

        const verifyRes = await fetch('/wallet/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ wallet_address: address, signature, message, nonce }),
        });

        const data = await verifyRes.json();

        if (!verifyRes.ok) {
            const msg = data.error
                || (data.errors && Object.values(data.errors).flat().join(' '))
                || 'Vérification échouée. Rechargez la page (F5) et réessayez.';
            throw new Error(msg);
        }

        this.setStatus('✓ Wallet MetaMask connecté avec succès !', 'success');
        return data;
    }

    async connectFromButton(btn) {
        if (this.isConnecting) return;

        const original = btn.innerHTML;
        this.isConnecting = true;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>MetaMask...';

        try {
            await this.connect();
            setTimeout(() => { window.location.href = '/dashboard'; }, 800);
        } catch (e) {
            this.setStatus('✗ ' + e.message, 'error');
            btn.disabled = false;
            btn.innerHTML = original;
        } finally {
            this.isConnecting = false;
        }
    }
}

window.autoChainWallet = new AutoChainWallet();

if (window.ethereum) {
    window.ethereum.on('accountsChanged', () => {
        if (!window.autoChainWallet.isConnecting && window.location.pathname.includes('wallet')) {
            window.location.reload();
        }
    });
}
