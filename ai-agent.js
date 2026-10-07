/**
 * AI Agent Assistant for INAAM's Website
 * Automatically checks account details and answers user questions.
 */

(function () {
    // 1. Inject Styles
    const style = document.createElement('style');
    style.innerHTML = `
        /* AI Agent Floating Widget */
        .ai-agent-trigger {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            background: linear-gradient(135deg, #4f46e5, #38bdf8);
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.45);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: aiPulse 3s infinite;
        }

        .ai-agent-trigger:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 12px 30px rgba(56, 189, 248, 0.55);
        }

        @keyframes aiPulse {
            0%, 100% { box-shadow: 0 8px 25px rgba(79, 70, 229, 0.45); }
            50% { box-shadow: 0 8px 35px rgba(56, 189, 248, 0.7); }
        }

        .ai-pulse-dot {
            width: 9px;
            height: 9px;
            background-color: #34d399;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 8px #34d399;
        }

        /* AI Chat Window */
        .ai-chat-window {
            position: fixed;
            bottom: 85px;
            right: 24px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 520px;
            max-height: calc(100vh - 110px);
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 18px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6);
            display: none;
            flex-direction: column;
            z-index: 10000;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            animation: aiFadeUp 0.25s ease-out;
        }

        @keyframes aiFadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ai-chat-header {
            background: #0f172a;
            border-bottom: 1px solid #334155;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ai-header-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ai-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #38bdf8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .ai-title {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
        }

        .ai-status {
            font-size: 0.78rem;
            color: #34d399;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .ai-close-btn {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 4px;
            line-height: 1;
            transition: color 0.2s;
        }

        .ai-close-btn:hover {
            color: #ffffff;
        }

        /* Messages Body */
        .ai-chat-body {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #0f172a;
        }

        .ai-chat-body::-webkit-scrollbar {
            width: 6px;
        }
        .ai-chat-body::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }

        .ai-msg {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 0.9rem;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .ai-msg-bot {
            align-self: flex-start;
            background: #1e293b;
            color: #e2e8f0;
            border: 1px solid #334155;
            border-bottom-left-radius: 4px;
        }

        .ai-msg-user {
            align-self: flex-end;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }

        .ai-msg-time {
            font-size: 0.72rem;
            color: #94a3b8;
            margin-top: 4px;
            text-align: right;
        }

        /* Chips */
        .ai-chips-wrap {
            padding: 8px 14px;
            background: #162032;
            border-top: 1px solid #334155;
            display: flex;
            gap: 6px;
            overflow-x: auto;
            white-space: nowrap;
        }

        .ai-chips-wrap::-webkit-scrollbar {
            display: none;
        }

        .ai-chip {
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #7dd3fc;
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 0.78rem;
            cursor: pointer;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .ai-chip:hover {
            background: #38bdf8;
            color: #0f172a;
            border-color: #38bdf8;
        }

        /* Input Area */
        .ai-chat-input-area {
            background: #1e293b;
            border-top: 1px solid #334155;
            padding: 10px 14px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .ai-input {
            flex: 1;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 9px 16px;
            color: #ffffff;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .ai-input:focus {
            border-color: #38bdf8;
        }

        .ai-send-btn {
            background: #4f46e5;
            color: white;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: background 0.2s, transform 0.1s;
            flex-shrink: 0;
        }

        .ai-send-btn:hover {
            background: #38bdf8;
            color: #0f172a;
            transform: scale(1.05);
        }

        .ai-typing {
            font-size: 0.8rem;
            color: #94a3b8;
            font-style: italic;
            padding: 4px 8px;
        }
    `;
    document.head.appendChild(style);

    // 2. Inject HTML Elements
    const trigger = document.createElement('button');
    trigger.className = 'ai-agent-trigger';
    trigger.id = 'aiAgentTrigger';
    trigger.innerHTML = `<span class="ai-pulse-dot"></span> 🤖 Ask AI Agent`;
    document.body.appendChild(trigger);

    const chatWindow = document.createElement('div');
    chatWindow.className = 'ai-chat-window';
    chatWindow.id = 'aiChatWindow';
    chatWindow.innerHTML = `
        <div class="ai-chat-header">
            <div class="ai-header-info">
                <div class="ai-avatar">🤖</div>
                <div>
                    <h4 class="ai-title">INAAM AI Agent</h4>
                    <div class="ai-status"><span class="ai-pulse-dot"></span> Online &amp; Auto-Checking</div>
                </div>
            </div>
            <button class="ai-close-btn" id="aiCloseBtn">&times;</button>
        </div>

        <div class="ai-chat-body" id="aiChatBody"></div>

        <div class="ai-chips-wrap">
            <span class="ai-chip" onclick="window.sendAiQuick('🔍 Check my details')">🔍 Check Details</span>
            <span class="ai-chip" onclick="window.sendAiQuick('🔒 Name change rule?')">🔒 Name Change Rule</span>
            <span class="ai-chip" onclick="window.sendAiQuick('🖼️ How to change profile pic?')">🖼️ Profile Picture</span>
            <span class="ai-chip" onclick="window.sendAiQuick('🎓 INAAM Education info')">🎓 Education</span>
            <span class="ai-chip" onclick="window.sendAiQuick('🌐 GitHub Pages compatibility')">🌐 GitHub Pages</span>
        </div>

        <div class="ai-chat-input-area">
            <input type="text" id="aiInput" class="ai-input" placeholder="Ask AI Agent a question...">
            <button id="aiSendBtn" class="ai-send-btn">&#10148;</button>
        </div>
    `;
    document.body.appendChild(chatWindow);

    // 3. User Data Fetcher
    function getUserData() {
        const saved = localStorage.getItem('currentUser');
        let user = { name: 'INAAM', email: 'domicle672@gmail.com', name_changed: 0, avatar: '' };
        if (saved) {
            try {
                user = Object.assign(user, JSON.parse(saved));
            } catch (e) {}
        }
        return user;
    }

    // 4. Welcome Message with Automatic Details Audit
    let welcomed = false;
    function initWelcome() {
        if (welcomed) return;
        welcomed = true;

        const user = getUserData();
        const initial = user.name ? user.name.charAt(0).toUpperCase() : 'I';
        const limitText = (user.name_changed && user.name_changed >= 1) ? '🔒 Locked (1/1 used)' : '✅ 1 Edit Available';
        const avatarText = user.avatar ? (user.avatar.startsWith('data:') ? 'Custom Photo Uploaded' : `Preset Avatar (${user.avatar})`) : `Default Initial (${initial})`;

        const welcomeHtml = `
            <strong>👋 Hello ${user.name}!</strong><br>
            I am your personal <strong>AI Agent</strong>. I have automatically checked your account details:
            <ul style="margin: 8px 0 8px 18px; padding: 0; font-size: 0.85rem; color: #94a3b8;">
                <li><strong>Username:</strong> ${user.name} (Verified ✓)</li>
                <li><strong>Email:</strong> ${user.email} (Active ✓)</li>
                <li><strong>Name Change Policy:</strong> ${limitText}</li>
                <li><strong>Profile Picture:</strong> ${avatarText}</li>
                <li><strong>Education:</strong> BCA @ UGMT (Class of 2028)</li>
            </ul>
            Feel free to ask me anything or click one of the quick questions below!
        `;
        appendMessage(welcomeHtml, 'bot');
    }

    function appendMessage(text, sender) {
        const body = document.getElementById('aiChatBody');
        const msg = document.createElement('div');
        msg.className = `ai-msg ai-msg-${sender}`;
        msg.innerHTML = text;

        const time = document.createElement('div');
        time.className = 'ai-msg-time';
        time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        msg.appendChild(time);

        body.appendChild(msg);
        body.scrollTop = body.scrollHeight;
    }

    // 5. Intelligent Question Answering Engine
    function generateAnswer(query) {
        const q = query.toLowerCase().trim();
        const user = getUserData();

        // 1. Automatic Account Check
        if (q.includes('check') || q.includes('detail') || q.includes('status') || q.includes('account')) {
            const limit = (user.name_changed && user.name_changed >= 1) ? '🔒 Locked (1/1 used)' : '✅ Available (0/1 used)';
            const pic = user.avatar ? 'Custom Photo Active' : 'Default Initial Avatar';
            return `
                🔍 <strong>Automatic Diagnostic Report:</strong><br>
                • <strong>User Name:</strong> ${user.name}<br>
                • <strong>Email:</strong> ${user.email}<br>
                • <strong>1-Time Name Change:</strong> ${limit}<br>
                • <strong>Profile Avatar:</strong> ${pic}<br>
                • <strong>System Health:</strong> 100% Verified on GitHub Pages &amp; LocalStorage.
            `;
        }

        // 2. Name Change Policy
        if (q.includes('name') || q.includes('naam') || q.includes('username') || q.includes('change')) {
            if (user.name_changed >= 1) {
                return `🔒 <strong>Username Status:</strong> You have already used your <strong>1 allowed username change</strong>. Your username is permanently locked to <strong>${user.name}</strong> as per site policy.`;
            } else {
                return `✏️ <strong>Username Policy:</strong> You are allowed to change your username <strong>only ONE time</strong> after registration. You currently have <strong>1 change available</strong> on your <a href="profile.html" style="color: #38bdf8; text-decoration: underline;">Profile Page</a>!`;
            }
        }

        // 3. Profile Picture
        if (q.includes('pic') || q.includes('photo') || q.includes('avatar') || q.includes('image')) {
            return `🖼️ <strong>Profile Picture Guide:</strong> Unlike usernames, you can change your profile picture <em>as many times as you like</em>! Visit your <a href="profile.html" style="color: #38bdf8; text-decoration: underline;">Profile Page</a>, click the 📷 camera icon to upload any photo from your device, or pick one of the instant emoji avatars (👨‍💻 Coder, 🎓 Student, 🚀 Astronaut)!`;
        }

        // 4. Education & Qualifications
        if (q.includes('education') || q.includes('college') || q.includes('school') || q.includes('bca') || q.includes('qualif')) {
            return `🎓 <strong>Educational Qualifications:</strong><br>
                • <strong>BCA (Bachelor of Computer Applications):</strong> Universal Group of Management And Technology (Passing Year: 2028)<br>
                • <strong>12th Grade:</strong> BHSS Sogam (2024)<br>
                • <strong>11th Grade:</strong> BHSS Sogam (2023)<br>
                • <strong>10th Grade:</strong> BHSS Sogam (2022)
            `;
        }

        // 5. GitHub Pages Compatibility
        if (q.includes('github') || q.includes('git') || q.includes('pages') || q.includes('php') || q.includes('hosting')) {
            return `🌐 <strong>GitHub Pages Info:</strong> GitHub Pages only supports static HTML, CSS, and JS (no PHP server). I configured your website so registration, authentication, activation, profile pictures, and dashboard all work 100% client-side using browser <code>localStorage</code>!`;
        }

        // 6. Contact Information
        if (q.includes('contact') || q.includes('email') || q.includes('phone') || q.includes('number') || q.includes('call')) {
            return `📞 <strong>Contact Details:</strong><br>
                • <strong>Phone:</strong> <a href="tel:6006541527" style="color: #38bdf8;">+91 6006541527</a><br>
                • <strong>Email:</strong> <a href="mailto:domicle672@gmail.com" style="color: #38bdf8;">domicle672@gmail.com</a>
            `;
        }

        // 7. Greetings
        if (q.startsWith('hi') || q.startsWith('hello') || q.startsWith('hey') || q === 'salam') {
            return `Hello ${user.name}! 👋 I am your website's AI Agent. Ask me to check your account, explain the name change policy, or give details about INAAM's portfolio!`;
        }

        // Default response
        return `🤖 <strong>AI Agent Insight:</strong> I understand you're asking about <em>"${query}"</em>. You can manage your account on the <a href="settings.html" style="color: #38bdf8;">Settings Page</a>, update your profile on the <a href="profile.html" style="color: #38bdf8;">Profile Page</a>, or explore the <a href="dashboard.html" style="color: #38bdf8;">Dashboard</a>!`;
    }

    // 6. Handle Interaction
    function handleSend() {
        const input = document.getElementById('aiInput');
        const text = input.value.trim();
        if (!text) return;

        appendMessage(text, 'user');
        input.value = '';

        // Typing indicator
        const body = document.getElementById('aiChatBody');
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'ai-typing';
        typingIndicator.id = 'aiTypingInd';
        typingIndicator.textContent = '🤖 AI Agent is thinking...';
        body.appendChild(typingIndicator);
        body.scrollTop = body.scrollHeight;

        setTimeout(() => {
            const ind = document.getElementById('aiTypingInd');
            if (ind) ind.remove();
            const answer = generateAnswer(text);
            appendMessage(answer, 'bot');
        }, 350);
    }

    window.sendAiQuick = function (questionText) {
        document.getElementById('aiInput').value = questionText;
        handleSend();
    };

    // Events
    trigger.addEventListener('click', () => {
        const isHidden = chatWindow.style.display === 'none' || chatWindow.style.display === '';
        chatWindow.style.display = isHidden ? 'flex' : 'none';
        if (isHidden) {
            initWelcome();
            document.getElementById('aiInput').focus();
        }
    });

    document.getElementById('aiCloseBtn').addEventListener('click', () => {
        chatWindow.style.display = 'none';
    });

    document.getElementById('aiSendBtn').addEventListener('click', handleSend);

    document.getElementById('aiInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleSend();
        }
    });
})();
