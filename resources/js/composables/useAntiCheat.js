import { ref, onMounted, onBeforeUnmount } from 'vue'
import Swal from 'sweetalert2'

// Inject CSS for anti-cheat Swal to have highest z-index
const style = document.createElement('style')
style.textContent = `
  .anti-cheat-swal-container {
    z-index: 99999 !important;
  }
  .swal2-container.anti-cheat-swal-container {
    z-index: 99999 !important;
  }
`
document.head.appendChild(style)

/**
 * Anti-cheat composable for examination system
 * Includes: Fullscreen lock, copy/paste prevention, suspicious activity detection
 */
export function useAntiCheat(options = {}) {
  const {
    onSubmit = null,           // Callback to submit test
    maxViolations = 3,         // Max violations before auto-submit
    enableFullscreen = true,   // Enable fullscreen enforcement
    enableCopyPaste = true,    // Enable copy/paste prevention
    enableTabSwitch = true,    // Enable tab switch detection
    logEndpoint = null,        // API endpoint to log activities
  } = options

  const violations = ref(0)
  const activities = ref([])
  const isFullscreen = ref(false)
  const tabSwitchCount = ref(0)
  const isActive = ref(false)
  const currentUrl = ref('')
  const urlCheckInterval = ref(null)
  const addressBarFocusCount = ref(0)

  // Log activity
  function logActivity(type, details = {}) {
    const activity = {
      type,
      details,
      timestamp: new Date().toISOString(),
    }
    
    activities.value.push(activity)
    console.warn('🚨 Suspicious Activity:', activity)

    // Send to backend if endpoint provided
    if (logEndpoint) {
      fetch(logEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(activity),
      }).catch(err => console.error('Failed to log activity:', err))
    }
  }

  // Handle violation
  async function handleViolation(type, message) {
    violations.value++
    logActivity('violation', { type, count: violations.value })

    if (violations.value >= maxViolations) {
      await Swal.fire({
        icon: 'error',
        title: 'Vi phạm quá nhiều!',
        html: `
          <p>Bạn đã vi phạm quy định làm bài <strong>${violations.value}</strong> lần.</p>
          <p>Bài thi sẽ được nộp tự động.</p>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: 'Đồng ý',
      })

      if (onSubmit) {
        await onSubmit()
      }
    } else {
      await Swal.fire({
        icon: 'warning',
        title: 'Cảnh báo!',
        html: `
          <p>${message}</p>
          <p>Vi phạm: <strong>${violations.value}/${maxViolations}</strong></p>
          <p class="text-red-600">Còn <strong>${maxViolations - violations.value}</strong> lần nữa bài thi sẽ tự động nộp!</p>
        `,
        confirmButtonText: 'Tôi hiểu',
      })
    }
  }

  // Fullscreen handlers
  function enterFullscreen() {
    console.log('🔒 Entering fullscreen...')
    
    const elem = document.documentElement
    
    if (elem.requestFullscreen) {
      elem.requestFullscreen().catch(err => {
        console.error('❌ Fullscreen request failed:', err)
      })
    } else if (elem.webkitRequestFullscreen) {
      elem.webkitRequestFullscreen()
    } else if (elem.msRequestFullscreen) {
      elem.msRequestFullscreen()
    }
  }

  function checkFullscreen() {
    const isFS = !!(
      document.fullscreenElement ||
      document.webkitFullscreenElement ||
      document.msFullscreenElement
    )
    
    isFullscreen.value = isFS
    return isFS
  }

  async function handleFullscreenChange() {
    const isFS = checkFullscreen()
    
    console.log('🔍 Fullscreen change detected:', { 
      isFullscreen: isFS, 
      enableFullscreen,
      isActive: isActive.value 
    })
    
    if (!isFS && enableFullscreen && isActive.value) {
      logActivity('fullscreen_exit', { tabSwitchCount: tabSwitchCount.value })
      
      await handleViolation(
        'fullscreen_exit',
        'Bạn đã thoát chế độ toàn màn hình! Vui lòng quay lại chế độ toàn màn hình để tiếp tục.'
      )
      
      // Re-enter fullscreen after warning
      if (violations.value < maxViolations) {
        enterFullscreen()
      }
    }
  }

  // Copy/Paste prevention
  function handleCopy(e) {
    if (enableCopyPaste) {
      e.preventDefault()
      logActivity('copy_attempt')
      
      Swal.fire({
        icon: 'warning',
        title: 'Không được phép copy!',
        text: 'Việc sao chép nội dung bài thi bị cấm.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
      })
    }
  }

  function handlePaste(e) {
    if (enableCopyPaste) {
      e.preventDefault()
      logActivity('paste_attempt')
      
      Swal.fire({
        icon: 'warning',
        title: 'Không được phép paste!',
        text: 'Việc dán nội dung từ ngoài bị cấm.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
      })
    }
  }

  function handleCut(e) {
    if (enableCopyPaste) {
      e.preventDefault()
      logActivity('cut_attempt')
    }
  }

  // Context menu prevention (right-click)
  function handleContextMenu(e) {
    e.preventDefault()
    logActivity('context_menu_attempt')
    return false
  }

  // Tab switch / Window blur detection
  function handleVisibilityChange() {
    if (document.hidden && enableTabSwitch) {
      tabSwitchCount.value++
      logActivity('tab_switch', { count: tabSwitchCount.value })
      
      handleViolation(
        'tab_switch',
        `Bạn đã chuyển tab/cửa sổ! Hãy tập trung vào bài thi.`
      )
    }
  }

  function handleBlur() {
    if (enableTabSwitch) {
      addressBarFocusCount.value++
      logActivity('window_blur', { 
        addressBarFocusCount: addressBarFocusCount.value 
      })
      
      // If repeatedly losing focus, it might be address bar usage
      if (addressBarFocusCount.value >= 2) {
        handleViolation(
          'address_bar_focus',
          'Phát hiện cố gắng điều hướng! Không được gõ URL hoặc thoát khỏi bài thi.'
        )
      }
    }
  }

  // Keyboard shortcuts detection
  function handleKeyDown(e) {
    const suspicious = []

    // Alt + Tab (partially detectable)
    if (e.altKey && e.key === 'Tab') {
      suspicious.push('Alt+Tab')
    }

    // Ctrl/Cmd + Tab
    if ((e.ctrlKey || e.metaKey) && e.key === 'Tab') {
      suspicious.push('Ctrl+Tab')
    }

    // Windows key (limited detection)
    if (e.key === 'Meta' || e.key === 'OS') {
      suspicious.push('Windows Key')
    }

    // F11 (fullscreen toggle)
    if (e.key === 'F11') {
      e.preventDefault()
      suspicious.push('F11')
    }

    // Ctrl/Cmd + W (close tab)
    if ((e.ctrlKey || e.metaKey) && e.key === 'w') {
      e.preventDefault()
      suspicious.push('Ctrl+W')
    }
    
    // Ctrl/Cmd + T (new tab)
    if ((e.ctrlKey || e.metaKey) && e.key === 't') {
      e.preventDefault()
      suspicious.push('New Tab')
      
      Swal.fire({
        icon: 'warning',
        title: 'Không được mở tab mới!',
        text: 'Mở tab mới sẽ bị coi là vi phạm.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
      })
    }
    
    // Ctrl/Cmd + N (new window)
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
      e.preventDefault()
      suspicious.push('New Window')
    }

    // Ctrl/Cmd + Shift + I (DevTools)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'I') {
      e.preventDefault()
      suspicious.push('DevTools')
    }

    // F12 (DevTools)
    if (e.key === 'F12') {
      e.preventDefault()
      suspicious.push('F12')
    }
    
    // Ctrl/Cmd + L (Address bar focus)
    if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
      e.preventDefault()
      suspicious.push('Address Bar')
      
      logActivity('address_bar_attempt')
      
      Swal.fire({
        icon: 'error',
        title: 'Bị chặn!',
        text: 'Không được phép gõ URL trong khi làm bài.',
        confirmButtonText: 'Đồng ý',
      })
    }
    
    // Alt + D (Address bar focus - Firefox)
    if (e.altKey && e.key === 'd') {
      e.preventDefault()
      suspicious.push('Address Bar')
    }
    
    // F5 / Ctrl+R (Reload)
    if (e.key === 'F5' || ((e.ctrlKey || e.metaKey) && e.key === 'r')) {
      e.preventDefault()
      suspicious.push('Reload')
      
      Swal.fire({
        icon: 'warning',
        title: 'Không được tải lại trang!',
        text: 'Tải lại trang sẽ làm mất dữ liệu bài thi.',
        confirmButtonText: 'Đồng ý',
      })
    }

    if (suspicious.length > 0) {
      logActivity('suspicious_keypress', { keys: suspicious })
      
      Swal.fire({
        icon: 'warning',
        title: 'Phím tắt bị chặn!',
        text: `Phím ${suspicious.join(', ')} không được phép sử dụng trong khi làm bài.`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
      })
    }
  }

  // Initialize anti-cheat
  async function initialize() {
    if (enableFullscreen) {
      // Show fullscreen prompt with HIGH z-index
      const result = await Swal.fire({
        icon: 'info',
        title: 'Chế độ làm bài',
        html: `
          <p>Để đảm bảo tính công bằng, bài thi sẽ được mở ở chế độ <strong>toàn màn hình</strong>.</p>
          <ul class="text-left mt-4 space-y-2">
            <li>✓ Không được thoát toàn màn hình</li>
            <li>✓ Không được copy/paste</li>
            <li>✓ Không được chuyển tab/cửa sổ</li>
            <li>✓ Vi phạm ${maxViolations} lần sẽ tự động nộp bài</li>
          </ul>
        `,
        confirmButtonText: 'Bắt đầu làm bài',
        allowOutsideClick: false,
        allowEscapeKey: false,
        customClass: {
          container: 'anti-cheat-swal-container'
        }
      })

      if (!result.isConfirmed) {
        // User cancelled - don't start test
        return false
      }

      // User confirmed - NOW activate anti-cheat
      isActive.value = true
      
      // Setup anti-cheat UI
      hideNavigationElements()
      enterFullscreen()
    } else {
      // No fullscreen required, activate immediately
      isActive.value = true
      hideNavigationElements()
    }

    // Add event listeners AFTER isActive is true
    document.addEventListener('fullscreenchange', handleFullscreenChange)
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange)
    document.addEventListener('msfullscreenchange', handleFullscreenChange)
    
    if (enableCopyPaste) {
      document.addEventListener('copy', handleCopy)
      document.addEventListener('paste', handlePaste)
      document.addEventListener('cut', handleCut)
      document.addEventListener('contextmenu', handleContextMenu)
    }
    
    if (enableTabSwitch) {
      document.addEventListener('visibilitychange', handleVisibilityChange)
      window.addEventListener('blur', handleBlur)
    }
    
    document.addEventListener('keydown', handleKeyDown)
    
    // Prevent browser back/forward
    window.history.pushState(null, '', window.location.href)
    window.addEventListener('popstate', handlePopState)
    
    // Prevent page reload/close
    window.addEventListener('beforeunload', handleBeforeUnload)
    
    // Monitor URL changes (detect address bar usage)
    currentUrl.value = window.location.href
    startUrlMonitoring()
    
    // Add focus trap to keep focus in test area
    document.addEventListener('focusout', handleFocusOut)
    
    // Return true to indicate successful initialization
    return true
  }
  
  // Prevent focus leaving test area
  function handleFocusOut(e) {
    // If focus is leaving the test container, bring it back
    const testContainer = document.querySelector('.test-player') || 
                          document.querySelector('.ielts-full-test')
    
    if (testContainer && !testContainer.contains(e.relatedTarget)) {
      logActivity('focus_escape', { 
        target: e.target?.tagName,
        relatedTarget: e.relatedTarget?.tagName 
      })
      
      // Refocus on test container
      setTimeout(() => {
        const firstInput = testContainer.querySelector('input, textarea, button, select')
        if (firstInput) {
          firstInput.focus()
        } else {
          testContainer.focus()
        }
      }, 10)
    }
  }

  // Monitor URL changes
  function startUrlMonitoring() {
    // Check URL every 500ms
    urlCheckInterval.value = setInterval(() => {
      const newUrl = window.location.href
      
      if (newUrl !== currentUrl.value) {
        logActivity('url_change', { 
          from: currentUrl.value, 
          to: newUrl 
        })
        
        // If URL changed but we're still in anti-cheat mode, it's a violation
        if (isActive.value) {
          handleViolation(
            'unauthorized_navigation',
            'Phát hiện thay đổi URL! Bạn không được phép điều hướng trong khi làm bài.'
          )
        }
        
        currentUrl.value = newUrl
      }
    }, 500)
  }
  
  // Stop URL monitoring
  function stopUrlMonitoring() {
    if (urlCheckInterval.value) {
      clearInterval(urlCheckInterval.value)
      urlCheckInterval.value = null
    }
  }

  // Handle page reload/close attempt
  function handleBeforeUnload(e) {
    e.preventDefault()
    e.returnValue = 'Bạn đang làm bài thi. Bạn có chắc chắn muốn thoát?'
    
    logActivity('page_unload_attempt')
    
    return e.returnValue
  }

  // Handle browser back/forward
  function handlePopState(e) {
    e.preventDefault()
    window.history.pushState(null, '', window.location.href)
    
    logActivity('navigation_attempt', { type: 'browser_back' })
    
    Swal.fire({
      icon: 'warning',
      title: 'Không được phép!',
      text: 'Bạn không thể quay lại trong khi làm bài.',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
    })
  }

  // Hide/disable navigation elements
  function hideNavigationElements() {
    // Hide sidebar
    const sidebar = document.querySelector('aside.sidebar') || 
                    document.querySelector('.sidebar') ||
                    document.querySelector('nav.sidebar') ||
                    document.querySelector('[class*="sidebar"]')
    
    if (sidebar) {
      sidebar.style.display = 'none'
      sidebar.setAttribute('data-anti-cheat-hidden', 'true')
    }

    // Hide top navigation (except test header)
    const topNav = document.querySelector('header:not(.test-header)') ||
                   document.querySelector('nav:not(.test-nav)')
    
    if (topNav && !topNav.closest('.test-player') && !topNav.closest('.ielts-full-test')) {
      topNav.style.display = 'none'
      topNav.setAttribute('data-anti-cheat-hidden', 'true')
    }

    // Disable all navigation links (but don't prevent scrolling)
    const navLinks = document.querySelectorAll('a[href]:not(.test-allowed)')
    navLinks.forEach(link => {
      if (!link.closest('.test-player') && !link.closest('.ielts-full-test')) {
        link.style.pointerEvents = 'none'
        link.style.opacity = '0.5'
        link.setAttribute('data-anti-cheat-disabled', 'true')
        
        // Prevent navigation
        link.addEventListener('click', preventNavigation)
      }
    })

    // NO OVERLAY - just ensure test container is full screen and scrollable
    const testContainer = document.querySelector('.test-player') || 
                          document.querySelector('.ielts-full-test') ||
                          document.querySelector('main')
    
    if (testContainer) {
      testContainer.style.position = 'fixed'
      testContainer.style.top = '0'
      testContainer.style.left = '0'
      testContainer.style.right = '0'
      testContainer.style.bottom = '0'
      testContainer.style.overflow = 'auto' // Allow scrolling
      testContainer.style.zIndex = '9999'
      testContainer.style.backgroundColor = '#fff'
    }
  }

  // Restore navigation elements
  function restoreNavigationElements() {
    // Restore sidebar
    const hiddenElements = document.querySelectorAll('[data-anti-cheat-hidden]')
    hiddenElements.forEach(el => {
      el.style.display = ''
      el.removeAttribute('data-anti-cheat-hidden')
    })

    // Restore navigation links
    const disabledLinks = document.querySelectorAll('[data-anti-cheat-disabled]')
    disabledLinks.forEach(link => {
      link.style.pointerEvents = ''
      link.style.opacity = ''
      link.removeAttribute('data-anti-cheat-disabled')
      link.removeEventListener('click', preventNavigation)
    })

    // Reset test container styles
    const testContainer = document.querySelector('.test-player') || 
                          document.querySelector('.ielts-full-test') ||
                          document.querySelector('main')
    
    if (testContainer) {
      testContainer.style.position = ''
      testContainer.style.top = ''
      testContainer.style.left = ''
      testContainer.style.right = ''
      testContainer.style.bottom = ''
      testContainer.style.overflow = ''
      testContainer.style.zIndex = ''
      testContainer.style.backgroundColor = ''
    }
  }

  // Prevent navigation during test
  function preventNavigation(e) {
    e.preventDefault()
    e.stopPropagation()
    
    logActivity('navigation_attempt', { 
      href: e.target.href,
      text: e.target.textContent 
    })
    
    Swal.fire({
      icon: 'warning',
      title: 'Không được phép!',
      text: 'Bạn không thể điều hướng ra ngoài trong khi làm bài.',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
    })
    
    return false
  }

  // Cleanup
  function cleanup() {
    isActive.value = false
    
    // Stop URL monitoring
    stopUrlMonitoring()
    
    // Restore navigation
    restoreNavigationElements()
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange)
    document.removeEventListener('msfullscreenchange', handleFullscreenChange)
    document.removeEventListener('copy', handleCopy)
    document.removeEventListener('paste', handlePaste)
    document.removeEventListener('cut', handleCut)
    document.removeEventListener('contextmenu', handleContextMenu)
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    window.removeEventListener('blur', handleBlur)
    document.removeEventListener('keydown', handleKeyDown)
    window.removeEventListener('popstate', handlePopState)
    window.removeEventListener('beforeunload', handleBeforeUnload)
    document.removeEventListener('focusout', handleFocusOut)

    // Exit fullscreen
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {})
    }
  }

  // Get summary report
  function getSummary() {
    return {
      totalViolations: violations.value,
      tabSwitches: tabSwitchCount.value,
      activities: activities.value,
      activityCounts: {
        copy: activities.value.filter(a => a.type === 'copy_attempt').length,
        paste: activities.value.filter(a => a.type === 'paste_attempt').length,
        tabSwitch: activities.value.filter(a => a.type === 'tab_switch').length,
        fullscreenExit: activities.value.filter(a => a.type === 'fullscreen_exit').length,
        suspiciousKeys: activities.value.filter(a => a.type === 'suspicious_keypress').length,
      }
    }
  }

  return {
    violations,
    activities,
    isFullscreen,
    tabSwitchCount,
    isActive,
    initialize,
    cleanup,
    getSummary,
    enterFullscreen,
    logActivity,
  }
}

