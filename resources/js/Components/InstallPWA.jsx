import { useState, useEffect } from 'react';

export default function InstallPWA() {
    const [deferredPrompt, setDeferredPrompt] = useState(null);
    const [isInstalled, setIsInstalled] = useState(false);
    const [showInstructions, setShowInstructions] = useState(false);
    const [isDismissed, setIsDismissed] = useState(false);
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        // Check if dismissed previously
        if (localStorage.getItem('pwa_prompt_dismissed') === 'true') {
            setIsDismissed(true);
        }

        // Check if mobile
        const checkMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        setIsMobile(checkMobile);

        // Check if already installed natively, via our stored flag, or via PWA URL parameter
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isStoredAsInstalled = localStorage.getItem('pwa_installed') === 'true';
        const isPwaUrl = window.location.search.includes('source=pwa');
        
        if (isStandalone || isStoredAsInstalled || isPwaUrl) {
            setIsInstalled(true);
            // Sync the storage if it was genuinely opened as standalone or via PWA URL
            if ((isStandalone || isPwaUrl) && !isStoredAsInstalled) {
                localStorage.setItem('pwa_installed', 'true');
            }
        }

        const handleBeforeInstallPrompt = (e) => {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Stash the event so it can be triggered later.
            setDeferredPrompt(e);
        };

        const handleAppInstalled = () => {
            setIsInstalled(true);
            setDeferredPrompt(null);
            localStorage.setItem('pwa_installed', 'true');
        };

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);

        return () => {
            window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
            window.removeEventListener('appinstalled', handleAppInstalled);
        };
    }, []);

    const handleInstallClick = async () => {
        if (deferredPrompt) {
            // Show the native install prompt
            deferredPrompt.prompt();
            // Wait for the user to respond to the prompt
            const { outcome } = await deferredPrompt.userChoice;
            // We've used the prompt, and can't use it again, throw it away
            setDeferredPrompt(null);
        } else {
            // Native prompt not available (e.g. iOS, or not HTTPS), show manual instructions
            setShowInstructions(true);
        }
    };

    const handleDismiss = () => {
        setIsDismissed(true);
        localStorage.setItem('pwa_prompt_dismissed', 'true');
    };

    // Only show on mobile devices if not installed and not dismissed
    if (isInstalled || isDismissed || !isMobile) return null;

    return (
        <>
            <div className="fixed bottom-20 left-4 z-40 md:bottom-6 md:left-6">
                <div className="relative group">
                    {/* Small dismiss badge */}
                    <button 
                        onClick={handleDismiss}
                        className="absolute -top-1 -right-1 w-5 h-5 bg-white dark:bg-gray-800 text-gray-500 rounded-full flex items-center justify-center shadow hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors z-10 border border-gray-200 dark:border-gray-700"
                        aria-label="Dismiss install prompt"
                    >
                        <i className="fas fa-times text-[9px]"></i>
                    </button>
                    
                    {/* Main circular install button */}
                    <button 
                        onClick={handleInstallClick}
                        className="w-14 h-14 bg-primary-600 dark:bg-primary-700 text-white rounded-full flex items-center justify-center shadow-xl hover:bg-primary-700 hover:scale-105 transition-all focus:outline-none focus:ring-4 focus:ring-primary-500/30"
                        aria-label="Install App"
                        title="Install App"
                    >
                        <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* Manual Instructions Modal */}
            {showInstructions && (
                <div className="fixed inset-0 z-50 flex items-center justify-center px-4">
                    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setShowInstructions(false)}></div>
                    <div className="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl relative z-10 max-w-sm w-full">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">How to Install</h3>
                        <div className="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                            <p>It looks like we can't install the app automatically on your current browser. You can install it manually in a few taps:</p>
                            
                            <div className="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                                <p className="font-semibold text-gray-900 dark:text-white mb-2">On iPhone/iPad (Safari):</p>
                                <ol className="list-decimal pl-4 space-y-1">
                                    <li>Tap the <i className="fas fa-share-square mx-1"></i> <strong>Share</strong> button at the bottom.</li>
                                    <li>Scroll down and tap <strong>"Add to Home Screen" <i className="fas fa-plus-square mx-1"></i></strong>.</li>
                                    <li>Tap <strong>Add</strong> in the top right.</li>
                                </ol>
                            </div>

                            <div className="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">
                                <p className="font-semibold text-gray-900 dark:text-white mb-2">On Android (Chrome):</p>
                                <ol className="list-decimal pl-4 space-y-1">
                                    <li>Tap the <i className="fas fa-ellipsis-v mx-1"></i> <strong>Menu</strong> icon in the top right.</li>
                                    <li>Tap <strong>"Install App"</strong> or <strong>"Add to Home screen"</strong>.</li>
                                </ol>
                            </div>
                        </div>
                        <button 
                            onClick={() => setShowInstructions(false)}
                            className="w-full mt-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition-colors"
                        >
                            Got it!
                        </button>
                    </div>
                </div>
            )}
        </>
    );
}
