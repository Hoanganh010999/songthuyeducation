<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Customer;
use App\Models\CustomerChild;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Lấy ví của user hiện tại
     * Priority: CustomerChild Wallet > Customer Wallet > User Wallet
     */
    public function myWallet()
    {
        $user = auth()->user();
        
        \Log::info('🔍 WALLET: Loading wallet for user', [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
        
        // Priority 1: Check if user is a CustomerChild (student who is a child)
        $customerChild = CustomerChild::where('user_id', $user->id)->first();
        
        if ($customerChild) {
            \Log::info('👶 WALLET: User is a CustomerChild', [
                'customer_child_id' => $customerChild->id,
                'child_name' => $customerChild->name,
            ]);
            
            // Load CustomerChild's wallet
            $wallet = Wallet::where('owner_type', CustomerChild::class)
                ->where('owner_id', $customerChild->id)
                ->with('transactions')
                ->first();
                
            if ($wallet) {
                \Log::info('💰 WALLET: CustomerChild wallet loaded', [
                    'wallet_id' => $wallet->id,
                    'balance' => $wallet->balance,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Lấy thông tin ví thành công',
                    'data' => $wallet
                ]);
            }
        }
        
        // Priority 2: Check if user has a linked Customer account
        $customer = Customer::where('user_id', $user->id)->first();
        
        if ($customer) {
            \Log::info('✅ WALLET: User has linked Customer account', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);
            
            // Load Customer's wallet
            $wallet = Wallet::where('owner_type', Customer::class)
                ->where('owner_id', $customer->id)
                ->with('transactions')
                ->first();
                
            if ($wallet) {
                \Log::info('💰 WALLET: Customer wallet loaded', [
                    'wallet_id' => $wallet->id,
                    'balance' => $wallet->balance,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Lấy thông tin ví thành công',
                    'data' => $wallet
                ]);
            }
        }
        
        // Priority 3: Fallback to User's wallet
        \Log::info('🔍 WALLET: No customer/child wallet, trying User wallet', [
            'user_id' => $user->id,
        ]);
        
        $wallet = Wallet::where('owner_type', \App\Models\User::class)
            ->where('owner_id', $user->id)
            ->with('transactions')
            ->first();

        if (!$wallet) {
            \Log::info('ℹ️ WALLET: No wallet found for user');
            
            return response()->json([
                'success' => true,
                'message' => 'Tài khoản này không có ví',
                'data' => null
            ]);
        }

        \Log::info('💰 WALLET: User wallet loaded', [
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin ví thành công',
            'data' => $wallet
        ]);
    }

    /**
     * Lấy ví của customer hoặc child
     */
    public function show(Request $request)
    {
        $request->validate([
            'owner_type' => 'required|in:customer,child',
            'owner_id' => 'required|integer',
        ]);

        $ownerType = $request->owner_type === 'customer' 
            ? Customer::class 
            : CustomerChild::class;

        $wallet = Wallet::where('owner_type', $ownerType)
            ->where('owner_id', $request->owner_id)
            ->with('transactions')
            ->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Ví không tồn tại'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $wallet
        ]);
    }

    /**
     * Lấy lịch sử giao dịch
     */
    public function transactions(Request $request)
    {
        $request->validate([
            'owner_type' => 'required|in:customer,child',
            'owner_id' => 'required|integer',
        ]);

        $ownerType = $request->owner_type === 'customer' 
            ? Customer::class 
            : CustomerChild::class;

        $wallet = Wallet::where('owner_type', $ownerType)
            ->where('owner_id', $request->owner_id)
            ->firstOrFail();

        $perPage = $request->input('per_page', 20);
        $transactions = $wallet->transactions()
            ->with('transactionable', 'creator')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Lấy tất cả ví của customer (bao gồm cả ví của các con)
     */
    public function customerWallets(string $customerId)
    {
        $customer = Customer::with(['children'])->findOrFail($customerId);

        $wallets = [];

        // Customer wallet
        $customerWallet = $customer->wallet;
        if ($customerWallet) {
            $wallets[] = [
                'type' => 'customer',
                'owner' => $customer,
                'wallet' => $customerWallet,
            ];
        }

        // Children wallets
        foreach ($customer->children as $child) {
            $childWallet = $child->wallet;
            if ($childWallet) {
                $wallets[] = [
                    'type' => 'child',
                    'owner' => $child,
                    'wallet' => $childWallet,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $wallets
        ]);
    }

    /**
     * Lấy tất cả ví của các con (cho parent)
     */
    public function myChildrenWallets()
    {
        $user = auth()->user();
        
        \Log::info('🔍 WALLET: Loading children wallets for parent', [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
        
        // Check if user is a parent
        $parent = \App\Models\ParentModel::where('user_id', $user->id)->first();
        
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải là phụ huynh',
                'data' => []
            ], 403);
        }
        
        // Get all children's wallets
        $children = $parent->students()->with(['user', 'wallet'])->get();
        
        $wallets = [];
        
        foreach ($children as $student) {
            // Check if student is also a CustomerChild
            $customerChild = CustomerChild::where('user_id', $student->user_id)->first();
            
            $childWallet = null;
            
            // Priority: CustomerChild wallet > Student wallet
            if ($customerChild) {
                $childWallet = Wallet::where('owner_type', CustomerChild::class)
                    ->where('owner_id', $customerChild->id)
                    ->with('transactions')
                    ->first();
            }
            
            // Fallback to student's user wallet
            if (!$childWallet && $student->user) {
                $childWallet = Wallet::where('owner_type', \App\Models\User::class)
                    ->where('owner_id', $student->user_id)
                    ->with('transactions')
                    ->first();
            }
            
            if ($childWallet) {
                $wallets[] = [
                    'student_id' => $student->id,
                    'student_code' => $student->student_code,
                    'student_name' => $student->user->name ?? $student->name ?? 'N/A',
                    'wallet' => $childWallet,
                ];
            }
        }
        
        \Log::info('✅ WALLET: Loaded wallets for children', [
            'parent_id' => $parent->id,
            'children_count' => $children->count(),
            'wallets_count' => count($wallets),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin ví của các con thành công',
            'data' => $wallets
        ]);
    }

    /**
     * Khóa/Mở khóa ví
     */
    public function toggleLock(Request $request, string $id)
    {
        $validated = $request->validate([
            'is_locked' => 'required|boolean',
            'lock_reason' => 'nullable|string',
        ]);

        $wallet = Wallet::findOrFail($id);

        $wallet->update([
            'is_locked' => $validated['is_locked'],
            'lock_reason' => $validated['lock_reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['is_locked'] ? 'Đã khóa ví' : 'Đã mở khóa ví',
            'data' => $wallet->fresh()
        ]);
    }
}

