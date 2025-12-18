import { ref, computed } from 'vue';
import axios from 'axios';

// Global state for active Zalo account
const activeAccountId = ref(null);
const activeAccount = ref(null);
const accounts = ref([]);
const loading = ref(false);

export function useZaloAccount() {
    /**
     * Load all accounts
     */
    const loadAccounts = async () => {
        loading.value = true;
        try {
            const response = await axios.get('/api/zalo/accounts');
            if (response.data.success) {
                accounts.value = response.data.data || [];
                
                // Find and set active account
                const active = accounts.value.find(acc => acc.is_active);
                if (active) {
                    activeAccountId.value = active.id;
                    activeAccount.value = active;
                } else if (accounts.value.length > 0) {
                    // If no active account but accounts exist, set first one as active
                    await setActiveAccount(accounts.value[0].id);
                }
            }
        } catch (error) {
            console.error('Failed to load accounts:', error);
        } finally {
            loading.value = false;
        }
    };

    /**
     * Load active account
     */
    const loadActiveAccount = async () => {
        try {
            // First, get all accounts to get updated is_connected status
            const accountsResponse = await axios.get('/api/zalo/accounts');
            if (accountsResponse.data.success && accountsResponse.data.data) {
                accounts.value = accountsResponse.data.data;
                
                // Find active account from the list
                const active = accountsResponse.data.data.find(acc => acc.is_active);
                if (active) {
                    activeAccount.value = active;
                    activeAccountId.value = active.id;
                    return;
                }
            }
            
            // Fallback: try to get active account directly
            const response = await axios.get('/api/zalo/accounts/active');
            if (response.data.success && response.data.data) {
                activeAccount.value = response.data.data;
                activeAccountId.value = response.data.data.id;
            } else {
                // No active account, try to load all accounts
                activeAccount.value = null;
                activeAccountId.value = null;
                await loadAccounts();
            }
        } catch (error) {
            // Error means no active account, try to load all accounts
            activeAccount.value = null;
            activeAccountId.value = null;
            await loadAccounts();
        }
    };

    /**
     * Set active account
     */
    const setActiveAccount = async (accountId) => {
        try {
            await axios.post('/api/zalo/accounts/active', { account_id: accountId });
            activeAccountId.value = accountId;
            
            // Reload accounts to get updated state
            await loadAccounts();
            
            // Update activeAccount from accounts list
            const account = accounts.value.find(acc => acc.id === accountId);
            if (account) {
                activeAccount.value = account;
            }
            
            // Emit event for other components to react
            window.dispatchEvent(new CustomEvent('zalo-account-changed', { 
                detail: { accountId, account: activeAccount.value } 
            }));
            
            return true;
        } catch (error) {
            console.error('Failed to set active account:', error);
            return false;
        }
    };

    /**
     * Get account ID for API calls
     */
    const getAccountId = () => {
        return activeAccountId.value;
    };

    /**
     * Check if there's an active account
     */
    const hasActiveAccount = computed(() => {
        return activeAccountId.value !== null && activeAccount.value !== null;
    });

    /**
     * Initialize - load active account on mount
     */
    const init = async () => {
        await loadActiveAccount();
    };

    return {
        // State
        activeAccountId,
        activeAccount,
        accounts,
        loading,
        hasActiveAccount,
        
        // Methods
        init,
        loadAccounts,
        loadActiveAccount,
        setActiveAccount,
        getAccountId,
    };
}

