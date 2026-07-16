import { useState, useEffect, useRef, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';

export default function SupportChat() {
    const user = usePage().props.auth?.user;
    const isGuest = !user;
    const savedPhone = localStorage.getItem('support_phone');
    const [open, setOpen] = useState(false);
    const [step, setStep] = useState(isGuest && localStorage.getItem('support_token') ? 'loading' : (isGuest ? 'phone' : 'loading'));
    const [phone, setPhone] = useState(isGuest ? savedPhone || '' : '');
    const [token, setToken] = useState(null);
    const tokenRef = useRef(null);
    const [messages, setMessages] = useState([]);
    const [input, setInput] = useState('');
    const [sending, setSending] = useState(false);
    const [error, setError] = useState('');
    const chatEnd = useRef(null);
    const polling = useRef(null);

    const setTokenState = useCallback((val) => {
        tokenRef.current = val;
        setToken(val);
    }, []);

    const loadMessages = useCallback(async (tokenOverride) => {
        const t = tokenOverride || tokenRef.current;
        if (!t) return;
        try {
            const res = await axios.get('/support/messages', { params: { token: t } });
            if (res.data.success) {
                setMessages(res.data.messages);
                if (res.data.ticket?.status === 'closed') {
                    setStep('closed');
                }
            }
        } catch {
            localStorage.removeItem('support_token');
            setTokenState(null);
            setStep(isGuest ? 'phone' : 'loading');
            setMessages([]);
        }
    }, [isGuest, setTokenState]);

    useEffect(() => {
        if (isGuest) {
            const saved = localStorage.getItem('support_token');
            if (saved) {
                setTokenState(saved);
                setStep('chat');
                loadMessages(saved);
                return;
            }
            const savedPh = localStorage.getItem('support_phone');
            if (savedPh) {
                setPhone(savedPh);
                axios.post('/support/start', { phone: savedPh })
                    .then(res => {
                        if (res.data?.success) {
                            setTokenState(res.data.token);
                            localStorage.setItem('support_token', res.data.token);
                            setStep('chat');
                            loadMessages(res.data.token);
                        }
                    })
                    .catch(() => setError('Failed to reconnect.'));
            }
            return;
        }

        axios.post('/support/start')
            .then(res => {
                if (res.data?.success) {
                    localStorage.removeItem('support_token');
                    localStorage.removeItem('support_phone');
                    setTokenState(res.data.token);
                    setStep('chat');
                    loadMessages(res.data.token);
                } else {
                    setError('Failed to start chat.');
                }
            })
            .catch(() => setError('Connection error. Try again.'));
    }, []);

    useEffect(() => {
        if (step === 'chat') {
            chatEnd.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages, step]);

    useEffect(() => {
        if (step === 'chat' && tokenRef.current) {
            polling.current = setInterval(() => {
                loadMessages();
            }, 5000);
        }
        return () => {
            if (polling.current) clearInterval(polling.current);
        };
    }, [step, token, loadMessages]);

    const handlePhoneSubmit = async (e) => {
        e.preventDefault();
        if (!phone.trim()) return;
        setError('');
        try {
            const res = await axios.post('/support/start', { phone: phone.trim() });
            if (res.data.success) {
                setTokenState(res.data.token);
                localStorage.setItem('support_phone', phone.trim());
                localStorage.setItem('support_token', res.data.token);
                setStep('chat');
                loadMessages(res.data.token);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to start chat. Try again.');
        }
    };

    const handleSend = async (e) => {
        e.preventDefault();
        if (!input.trim() || sending) return;
        setSending(true);
        setError('');
        try {
            const res = await axios.post('/support/message', { message: input.trim(), token: tokenRef.current });
            if (res.data.success) {
                setMessages(prev => [...prev, res.data.message]);
                if (res.data.bot_message) {
                    setMessages(prev => [...prev, res.data.bot_message]);
                }
                setInput('');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to send. Try again.');
        }
        setSending(false);
    };

    return (
        <>
            <button onClick={() => setOpen(!open)}
                className="fixed md:bottom-5 bottom-24 right-5 z-50 w-14 h-14 rounded-full bg-primary-600 text-white shadow-xl hover:bg-primary-700 transition-all flex items-center justify-center hover:scale-105"
                style={{ boxShadow: '0 4px 20px rgba(0,0,0,0.25)' }}
                aria-label="Chat">
                {open ? (
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                ) : (
                    <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                )}
            </button>

            {open && (
                <div className="fixed md:bottom-20 bottom-40 right-5 max-md:left-5 max-md:right-5 max-md:w-auto z-50 md:w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-neutral-200 overflow-hidden flex flex-col"
                    style={{ boxShadow: '0 8px 40px rgba(0,0,0,0.18)', height: '500px', maxHeight: '70vh' }}>
                    <div className="bg-primary-600 px-5 py-4 flex items-center justify-between shrink-0">
                        <div className="flex items-center gap-3">
                            <div className="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center">
                                <svg className="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                            </div>
                            <div>
                                <p className="text-white font-semibold text-sm">AirLinks Bot</p>
                                <p className="text-white/70 text-xs">I reply instantly 🤖</p>
                            </div>
                        </div>
                        <button onClick={() => setOpen(false)} className="text-white/60 hover:text-white transition">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {step === 'loading' && (
                        <div className="flex-1 flex items-center justify-center p-6">
                            {error ? (
                                <div className="text-center space-y-3">
                                    <p className="text-red-600 text-sm">{error}</p>
                                    <button onClick={() => { setError(''); localStorage.removeItem('support_token'); setTokenState(null); setStep(isGuest ? 'phone' : 'loading'); }}
                                        className="text-primary-600 text-sm font-semibold hover:underline">
                                        Retry
                                    </button>
                                </div>
                            ) : (
                                <div className="w-8 h-8 border-2 border-primary-600 border-t-transparent rounded-full animate-spin" />
                            )}
                        </div>
                    )}

                    {step === 'phone' && (
                        <div className="flex-1 flex items-center justify-center p-6">
                            <form onSubmit={handlePhoneSubmit} className="w-full space-y-4 text-center">
                                <div className="w-14 h-14 rounded-full bg-primary-50 flex items-center justify-center mx-auto">
                                    <svg className="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                </div>
                                <p className="text-neutral-700 font-medium">Start a conversation</p>
                                <p className="text-neutral-400 text-xs -mt-2">Enter your phone number to chat with us</p>
                                <input type="tel" value={phone} onChange={e => setPhone(e.target.value)}
                                    className="w-full px-4 py-3 text-sm border border-neutral-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none text-center"
                                    placeholder="024XXXXXXX" autoFocus />
                                {error && <p className="text-red-600 text-xs">{error}</p>}
                                <button type="submit"
                                    className="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold text-sm hover:bg-primary-700 transition">
                                    Start Chat
                                </button>
                            </form>
                        </div>
                    )}

                    {step === 'chat' && (
                        <>
                            <div className="flex-1 overflow-y-auto p-4 space-y-3 bg-neutral-50"
                                style={{ scrollBehavior: 'smooth' }}>
                                {messages.length === 0 && (
                                    <p className="text-neutral-400 text-xs text-center py-8">Send a message to start the conversation</p>
                                )}
                                {messages.map(msg => (
                                    <div key={msg.id} className={`flex ${msg.sender_type === 'user' ? 'justify-end' : 'justify-start'}`}>
                                        <div className={`max-w-[80%] rounded-2xl px-4 py-2.5 ${
                                            msg.sender_type === 'user'
                                                ? 'bg-primary-600 text-white rounded-br-md'
                                                : 'bg-white border border-neutral-200 text-neutral-800 rounded-bl-md shadow-sm'
                                        }`}>
                                            {(msg.sender_type === 'admin' || msg.sender_type === 'bot') && (
                                                <p className="text-xs font-semibold text-primary-600 mb-0.5">
                                                    {msg.sender_type === 'bot' ? '🤖 Bot' : 'Support'}
                                                </p>
                                            )}
                                            <p className="text-sm leading-relaxed whitespace-pre-wrap">{msg.message}</p>
                                            <p className={`text-xs mt-1 ${msg.sender_type === 'user' ? 'text-white/60' : 'text-neutral-400'}`}>
                                                {msg.created_at}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                <div ref={chatEnd} />
                            </div>

                            <form onSubmit={handleSend} className="p-3 border-t border-neutral-200 bg-white shrink-0">
                                {error && <p className="text-red-600 text-xs mb-2">{error}</p>}
                                <div className="flex gap-2">
                                    <input type="text" value={input} onChange={e => setInput(e.target.value)}
                                        className="flex-1 px-4 py-2.5 text-sm border border-neutral-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                                        placeholder="Type a message..." autoFocus />
                                    <button type="submit" disabled={sending || !input.trim()}
                                        className="bg-primary-600 text-white p-2.5 rounded-xl hover:bg-primary-700 disabled:opacity-40 transition shrink-0">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </>
                    )}

                    {step === 'closed' && (
                        <div className="flex-1 flex items-center justify-center p-6 text-center">
                            <div>
                                <div className="w-14 h-14 rounded-full bg-neutral-100 flex items-center justify-center mx-auto mb-3">
                                    <svg className="w-7 h-7 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p className="text-neutral-700 font-semibold">Conversation closed</p>
                                <p className="text-neutral-400 text-sm mt-1">This chat has been closed.</p>
                                <button onClick={() => {
                                    setMessages([]);
                                    setPhone('');
                                    setTokenState(null);
                                    localStorage.removeItem('support_phone');
                                    localStorage.removeItem('support_token');
                                    setStep(isGuest ? 'phone' : 'loading');
                                }}
                                    className="mt-4 text-sm text-primary-600 font-semibold hover:underline">
                                    Start new chat
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            )}
        </>
    );
}
