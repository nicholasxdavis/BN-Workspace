<script>
    function redditTool() {
        return {
            sidebarOpen: false,
            activeTab: 'dashboard',
            isLoading: false,
            currentAction: '',
            dashboardLoading: true,
            apiKey: null,
            stats: { karma: '...', averageUpvoteRate: '...', averageComments: '...' },
            topPosts: [],
            topPostsTitle: 'Your Top Posts (This Month)',
            composer: { subreddit: '', title: '', content: '' },
            analyzer: { subreddit: '', result: '', analyzedSubreddit: '' },
            summarizer: { url: '', result: '' },
            trendingChart: null,

            async init() {
                this.initParticles();
                await this.loadApiKey();
                this.loadDashboardData();
            },
            
            switchTab(tab) {
                this.activeTab = tab;
                this.sidebarOpen = false; // Close sidebar on tab change
            },

            async loadApiKey() {
                try {
                    const response = await fetch(`../../auth/key_handler.php?t=${Date.now()}`);
                    if (!response.ok) throw new Error(`Could not fetch API key. Status: ${response.status}`);
                    const data = await response.json();
                    if (data.success) this.apiKey = data.apiKey;
                    else throw new Error(data.error || 'Failed to get API key.');
                } catch (error) {
                    console.error("API Key Load Error:", error);
                    this.apiKey = null;
                    alert('AI features are disabled. Could not load the required API key.');
                }
            },
            
            async loadDashboardData() {
                this.dashboardLoading = true;
                try {
                    const response = await fetch(`./reddit_api.php?action=dashboard_data&t=${Date.now()}`);
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Failed to load data. Please re-authenticate Reddit if the issue persists. (Status: ${response.status} - ${errorText})`);
                    }
                    const data = await response.json();
                    if (data.success) {
                        this.stats = data.stats;
                        this.topPosts = data.topPosts;
                        this.topPostsTitle = data.topPostsTitle || 'Your Top Posts (This Month)';
                        if (data.trendingData && data.trendingData.labels && data.trendingData.labels.length > 0) {
                            this.renderTrendingChart(data.trendingData);
                        } else {
                            this.renderChartError();
                        }
                    } else {
                        throw new Error(data.error || 'Failed to load dashboard data.');
                    }
                } catch (error) {
                    console.error("Dashboard Load Error:", error);
                    alert(`Could not load your Reddit data. Error: ${error.message}`);
                    this.renderChartError();
                } finally {
                    this.dashboardLoading = false;
                }
            },
            
            async postToReddit() {
                if (!this.composer.subreddit || !this.composer.title || !this.composer.content) {
                    alert('Please fill out the subreddit, title, and content fields.');
                    return;
                }
                if (confirm('Are you sure you want to post this to Reddit?')) {
                    try {
                        const response = await fetch('./reddit_api.php?action=submit_post', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.composer)
                        });
                        const result = await response.json();
                        if (result.success) {
                            alert('Successfully posted to Reddit!');
                            this.composer = { subreddit: '', title: '', content: '' };
                        } else {
                            throw new Error(result.error || 'Failed to post.');
                        }
                    } catch (error) {
                        console.error("Post Error:", error);
                        alert(`Failed to post to Reddit. Error: ${error.message}`);
                    }
                }
            },

            async aiAction(type, inputData) {
                if (!this.apiKey) {
                    alert('The AI API Key is not configured. AI features are disabled.');
                    return;
                }
                if (this.isLoading) return;
                this.isLoading = true;
                this.currentAction = type;

                let prompt = '';
                let systemMessage = "You are an expert Reddit marketing and content assistant. Be insightful, concise, and format your responses using markdown (e.g., **bold**, *italic*, - for lists).";
                let targetElementId = '';

                try {
                    switch(type) {
                        case 'improve':
                            if (!inputData.content) throw new Error("Content is required to improve.");
                            systemMessage = "You are a Reddit content optimization expert. Refine user-provided text for maximum engagement and clarity for a specific subreddit audience.";
                            prompt = `Refine the following post content for the subreddit "${inputData.subreddit}". Keep the core message but improve tone, structure, and clarity. The post title is "${inputData.title}".\n\nOriginal Content:\n${inputData.content}\n\nReturn ONLY the improved post content.`;
                            targetElementId = 'content';
                            break;
                        case 'generate':
                            if (!inputData.subreddit || !inputData.title) throw new Error("Subreddit and title are required to generate content.");
                            systemMessage = "You are a creative Reddit post writer. Generate compelling post content based on a subreddit, title, and contextual info about the community.";
                            const subredditInfo = await this.getSubredditInfo(inputData.subreddit);
                            prompt = `Generate a Reddit post for "${inputData.subreddit}". Title: "${inputData.title}".\n\nSubreddit Context:\n${subredditInfo}\n\nWrite an engaging post. Return ONLY the generated content.`;
                            targetElementId = 'content';
                            break;
                        case 'analyze':
                            if (!inputData) throw new Error("Subreddit name is required for analysis.");
                            this.analyzer.analyzedSubreddit = inputData;
                            systemMessage = "You are a Reddit strategist. Provide a detailed, well-structured analysis of a subreddit with actionable insights for a content creator, using markdown for formatting.";
                            prompt = `Perform a comprehensive analysis of "${inputData}". Structure your response with these markdown sections:\n\n**Overall Summary:**\n\n**Key Topics & Themes:** (as a bulleted list)\n\n**Community Vibe & Tone:**\n\n**Content Opportunities:** (as a bulleted list)`;
                            targetElementId = 'analyzer-results-container';
                            this.analyzer.result = ' ';
                            break;
                        case 'summarize':
                            if (!inputData) throw new Error("A Reddit URL is required to summarize.");
                            systemMessage = "You are an AI specializing in summarizing online discussions. Distill a Reddit thread into key points, arguments, and overall sentiment.";
                            prompt = `Provide a detailed summary of this Reddit thread: ${inputData}. Cover the original post's main topic, prominent opinions, counter-arguments, and the overall sentiment, using markdown for structure.`;
                            targetElementId = 'summarizer-results-container';
                            this.summarizer.result = ' ';
                            break;
                        default: throw new Error("Invalid AI action type.");
                    }
                    await this.streamAIResponse(prompt, systemMessage, targetElementId, type);
                } catch (error) {
                    console.error("AI Action Error:", error);
                    alert("An error occurred: " + error.message);
                } finally {
                    this.isLoading = false;
                    this.currentAction = '';
                }
            },

            async getSubredditInfo(subreddit) {
                try {
                    const response = await fetch(`./reddit_api.php?action=get_subreddit_info&subreddit=${subreddit}`);
                    if (!response.ok) return "Could not fetch specific subreddit info.";
                    const data = await response.json();
                    return data.success ? `Description: ${data.info.description}\nRules: ${data.info.rules.join(', ')}` : "No specific info found.";
                } catch (error) {
                    console.error(error);
                    return "Could not fetch specific subreddit info.";
                }
            },

            formatAIResponse(text) {
                let formattedText = text
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>');
                
                formattedText = formattedText.replace(/^\s*[\*-]\s*(.*)$/gm, '<li>$1</li>');
                formattedText = formattedText.replace(/<\/li>\n<li>/g, '</li><li>');
                if(formattedText.includes('<li>')) {
                   formattedText = `<ul>${formattedText.match(/<li>.*?<\/li>/g).join('')}</ul>`;
                }
                
                return formattedText.replace(/\n/g, '<br>').replace(/<br><ul>/g, '<ul>').replace(/<\/ul><br>/g, '</ul>');
            },

            async streamAIResponse(prompt, systemMessage, elementId, actionType) {
                let targetElement;
                if (actionType === 'improve' || actionType === 'generate') {
                    // handled by model update
                } else {
                    targetElement = document.getElementById(elementId);
                    if (!targetElement) throw new Error(`Element with ID ${elementId} not found.`);
                    targetElement.innerHTML = '';
                }
                
                let fullResponse = '';
                const cursor = '<span class="typing-cursor"></span>';
                
                if(targetElement) targetElement.innerHTML = cursor;
                else this.composer.content = '';

                try {
                    const response = await fetch("https://openrouter.ai/api/v1/chat/completions", {
                        method: "POST",
                        headers: { "Authorization": `Bearer ${this.apiKey}`, "Content-Type": "application/json" },
                        body: JSON.stringify({
                            "model": "deepseek/deepseek-chat",
                            "messages": [ { "role": "system", "content": systemMessage }, { "role": "user", "content": prompt } ],
                            "stream": true
                        })
                    });

                    if (!response.ok) throw new Error(`API request failed with status ${response.status}`);
                    
                    const reader = response.body.getReader();
                    const decoder = new TextDecoder();

                    while (true) {
                        const { value, done } = await reader.read();
                        if (done) break;
                        
                        const chunk = decoder.decode(value, { stream: true });
                        const lines = chunk.split('\n').filter(line => line.startsWith('data: '));

                        for (const line of lines) {
                            const jsonStr = line.substring(6);
                            if (jsonStr.trim() === '[DONE]') continue;
                            const data = JSON.parse(jsonStr);
                            if (data.choices[0].delta.content) {
                                fullResponse += data.choices[0].delta.content;
                                if(targetElement) {
                                    targetElement.innerHTML = this.formatAIResponse(fullResponse) + cursor;
                                } else {
                                    this.composer.content = fullResponse;
                                }
                            }
                        }
                    }
                } catch (error) {
                    const errorMessage = `<p class="text-red-400">Error: ${error.message}</p>`;
                    if(targetElement) targetElement.innerHTML = errorMessage;
                    else this.composer.content = `AI Error: ${error.message}`;
                    throw error;
                } finally {
                    if(targetElement) {
                        targetElement.innerHTML = this.formatAIResponse(fullResponse);
                        if (actionType === 'analyze') this.analyzer.result = fullResponse;
                        if (actionType === 'summarize') this.summarizer.result = fullResponse;
                    }
                }
            },
            
            renderChartError() {
                const canvas = document.getElementById('trendingChart');
                if(!canvas) return;
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#a1a1aa';
                ctx.textAlign = 'center';
                ctx.fillText('Could not load trending data.', canvas.width / 2, canvas.height / 2);
            },

            renderTrendingChart(data) {
                if (this.trendingChart) this.trendingChart.destroy();
                const truncatedLabels = data.labels.map(label => label.length > 25 ? label.substring(0, 22) + '...' : label);
                const ctx = document.getElementById('trendingChart').getContext('2d');
                this.trendingChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: truncatedLabels,
                        datasets: [{
                            label: 'Upvotes (Past 24h)',
                            data: data.scores,
                            backgroundColor: 'rgba(212, 97, 28, 0.5)',
                            borderColor: 'rgba(212, 97, 28, 1)',
                            borderWidth: 1,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1a1a1a', titleColor: '#EEEEEE', bodyColor: '#EEEEEE',
                                borderColor: 'rgba(212, 97, 28, 1)', borderWidth: 1,
                                callbacks: { title: (items) => data.labels[items[0].dataIndex] }
                            }
                        },
                        scales: {
                            x: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#a1a1aa' } },
                            y: { grid: { display: false }, ticks: { color: '#a1a1aa' } }
                        }
                    }
                });
            },

            initParticles() {
                const canvas = document.getElementById('particle-bg');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                let particles = [];
                const particleCount = 70;

                const resizeCanvas = () => {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                };
                
                class Particle {
                    constructor() {
                        const colors = [`rgba(255, 255, 255, ${Math.random()*0.2+0.1})`, `rgba(212, 97, 28, ${Math.random()*0.3+0.1})`];
                        this.color = colors[Math.floor(Math.random() * colors.length)];
                        this.radius = Math.random() * 2 + 1;
                        this.x = Math.random() * canvas.width;
                        this.y = Math.random() * canvas.height;
                        this.vx = (Math.random() - 0.5) * 0.4;
                        this.vy = (Math.random() - 0.5) * 0.4;
                    }
                    draw() {
                        ctx.beginPath();
                        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                        ctx.fillStyle = this.color;
                        ctx.fill();
                    }
                    update() {
                        this.x += this.vx;
                        this.y += this.vy;
                        if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
                        if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
                    }
                }

                for (let i = 0; i < particleCount; i++) particles.push(new Particle());
                
                const animate = () => {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    particles.forEach(p => { p.update(); p.draw(); });
                    requestAnimationFrame(animate);
                };

                window.addEventListener('resize', resizeCanvas);
                resizeCanvas();
                animate();
            }
        }
    }
</script>
