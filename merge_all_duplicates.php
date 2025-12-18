<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZaloGroup;
use App\Models\ZaloConversation;
use App\Models\ZaloMessage;
use Illuminate\Support\Facades\DB;

echo "🔧 Merging ALL duplicate groups and conversations...\n\n";

DB::beginTransaction();
try {
    // 1. IELTS - K5.TN
    echo "1️⃣ IELTS - K5.TN\n";
    $k5Groups = ZaloGroup::where('name', 'IELTS - K5.TN')->where('zalo_account_id', 23)->orderBy('created_at')->get();
    if ($k5Groups->count() > 1) {
        $keepGroup = $k5Groups->first(); // Keep oldest (757)
        echo "   Keep Group ID {$keepGroup->id}: {$keepGroup->zalo_group_id}\n";
        
        foreach ($k5Groups->skip(1) as $dupGroup) {
            echo "   Delete Group ID {$dupGroup->id}: {$dupGroup->zalo_group_id}\n";
            $dupGroup->delete();
        }
        
        // Merge conversations
        $k5Convs = ZaloConversation::where('recipient_name', 'IELTS - K5.TN')
            ->where('zalo_account_id', 23)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get();
        
        if ($k5Convs->count() > 1) {
            $keepConv = $k5Convs->first();
            echo "   Keep Conv ID {$keepConv->id}: {$keepConv->recipient_id}\n";
            
            foreach ($k5Convs->skip(1) as $dupConv) {
                $msgCount = ZaloMessage::where('zalo_conversation_id', $dupConv->id)->count();
                echo "   Merge Conv ID {$dupConv->id}: {$dupConv->recipient_id} ({$msgCount} msgs)\n";
                
                ZaloMessage::where('zalo_conversation_id', $dupConv->id)
                    ->update(['zalo_conversation_id' => $keepConv->id]);
                $dupConv->delete();
            }
            
            // Update keep conversation with correct group ID
            $keepConv->update(['recipient_id' => $keepGroup->zalo_group_id]);
            echo "   ✅ Total: " . $keepConv->messages()->count() . " messages\n\n";
        }
    }
    
    // 2. R.E.C. TN PRE STARTER
    echo "2️⃣ R.E.C. TN PRE STARTER\n";
    $recGroups = ZaloGroup::where('name', 'R.E.C. TN PRE STARTER')->where('zalo_account_id', 23)->orderBy('created_at')->get();
    if ($recGroups->count() > 1) {
        $keepGroup = $recGroups->first();
        echo "   Keep Group ID {$keepGroup->id}: {$keepGroup->zalo_group_id}\n";
        
        foreach ($recGroups->skip(1) as $dupGroup) {
            echo "   Delete Group ID {$dupGroup->id}: {$dupGroup->zalo_group_id}\n";
            $dupGroup->delete();
        }
        
        $recConvs = ZaloConversation::where('recipient_name', 'R.E.C. TN PRE STARTER')
            ->where('zalo_account_id', 23)
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get();
        
        if ($recConvs->count() > 1) {
            $keepConv = $recConvs->first();
            echo "   Keep Conv ID {$keepConv->id}: {$keepConv->recipient_id}\n";
            
            foreach ($recConvs->skip(1) as $dupConv) {
                $msgCount = ZaloMessage::where('zalo_conversation_id', $dupConv->id)->count();
                echo "   Merge Conv ID {$dupConv->id}: {$dupConv->recipient_id} ({$msgCount} msgs)\n";
                
                ZaloMessage::where('zalo_conversation_id', $dupConv->id)
                    ->update(['zalo_conversation_id' => $keepConv->id]);
                $dupConv->delete();
            }
            
            $keepConv->update(['recipient_id' => $keepGroup->zalo_group_id]);
            echo "   ✅ Total: " . $keepConv->messages()->count() . " messages\n\n";
        }
    }
    
    // 3. R.E.C.TN - MOVERS 1
    echo "3️⃣ R.E.C.TN - MOVERS 1\n";
    $moversConvs = ZaloConversation::where('recipient_name', 'R.E.C.TN - MOVERS 1')
        ->where('zalo_account_id', 23)
        ->whereNull('deleted_at')
        ->orderBy('created_at')
        ->get();
    
    if ($moversConvs->count() > 1) {
        $keepConv = $moversConvs->first();
        echo "   Keep Conv ID {$keepConv->id}: {$keepConv->recipient_id}\n";
        
        foreach ($moversConvs->skip(1) as $dupConv) {
            $msgCount = ZaloMessage::where('zalo_conversation_id', $dupConv->id)->count();
            echo "   Merge Conv ID {$dupConv->id}: {$dupConv->recipient_id} ({$msgCount} msgs)\n";
            
            ZaloMessage::where('zalo_conversation_id', $dupConv->id)
                ->update(['zalo_conversation_id' => $keepConv->id]);
            $dupConv->delete();
        }
        echo "   ✅ Total: " . $keepConv->messages()->count() . " messages\n\n";
    }
    
    // 4. YT R.E.C - ISS 2
    echo "4️⃣ YT R.E.C - ISS 2\n";
    $issConvs = ZaloConversation::where('recipient_name', 'YT R.E.C - ISS 2')
        ->where('zalo_account_id', 23)
        ->whereNull('deleted_at')
        ->orderBy('created_at')
        ->get();
    
    if ($issConvs->count() > 1) {
        $keepConv = $issConvs->first();
        echo "   Keep Conv ID {$keepConv->id}: {$keepConv->recipient_id}\n";
        
        foreach ($issConvs->skip(1) as $dupConv) {
            $msgCount = ZaloMessage::where('zalo_conversation_id', $dupConv->id)->count();
            echo "   Merge Conv ID {$dupConv->id}: {$dupConv->recipient_id} ({$msgCount} msgs)\n";
            
            ZaloMessage::where('zalo_conversation_id', $dupConv->id)
                ->update(['zalo_conversation_id' => $keepConv->id]);
            $dupConv->delete();
        }
        echo "   ✅ Total: " . $keepConv->messages()->count() . " messages\n\n";
    }
    
    DB::commit();
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ All duplicates merged!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

