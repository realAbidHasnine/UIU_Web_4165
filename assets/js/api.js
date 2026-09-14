/**
 * SkillMatch Core API Client & Dynamic Data Layer
 * Connects frontend to Spring Boot REST Backend (http://localhost:8080/api)
 * Includes graceful dynamic store fallback when backend is offline.
 */

(function () {
  'use strict';

  const CONFIG = {
    BASE_URL: 'http://localhost:8080/api',
    TIMEOUT: 4000,
    STORAGE_KEY: 'skillmatch_db_v1',
    SESSION_KEY: 'skillmatch_session_v1'
  };

  // Pre-seeded rich mock database for dynamic rendering when Spring Boot is offline
  const INITIAL_MOCK_DATA = {
    currentUser: {
      id: 'f-101',
      name: 'Sarah Jenkins',
      email: 'sarah.jenkins@example.com',
      role: 'FREELANCER',
      title: 'Senior Frontend & React Specialist',
      avatar: '../assets/images/avatar-sarah.png',
      hourlyRate: 65,
      verifiedScore: 94,
      verified: true,
      bio: 'Over 6 years of experience building modern, responsive, and performance-critical web applications with React, TypeScript, and modern CSS.',
      skills: ['React', 'TypeScript', 'TailwindCSS', 'Next.js', 'REST APIs', 'Node.js'],
      earnings: 28450,
      completedJobs: 34,
      activeProposals: 3
    },
    jobs: [
      {
        id: 'job-1',
        title: 'Modern Fintech Dashboard in React & Spring Boot',
        category: 'web',
        budgetType: 'fixed',
        budget: '3000-5000',
        budgetDisplay: '$3,000 - $5,000',
        duration: '1-3-months',
        durationDisplay: '1 to 3 Months',
        level: 'Expert',
        posted: '2 hours ago',
        company: 'Apex Capital Partners',
        location: 'Remote (US/EU)',
        skills: ['React', 'TypeScript', 'Spring Boot', 'REST API', 'Chart.js'],
        desc: 'We are redesigning our enterprise wealth management dashboard. We require an experienced frontend developer who can build pixel-perfect interactive widgets, realtime chart components, and integrate smoothly with our Java Spring Boot microservices.',
        responsibilities: [
          'Develop reusable React 18 components with strict TypeScript types',
          'Integrate REST endpoints for balance, historical yields, and asset allocations',
          'Optimize rendering performance for tables with 10,000+ data rows',
          'Write clean unit tests and ensure WCAG 2.1 AA accessibility'
        ],
        proposalsCount: 6,
        suggestedRate: 4200
      },
      {
        id: 'job-2',
        title: 'Mobile Banking App UI/UX Redesign System',
        category: 'uiux',
        budgetType: 'hourly',
        budget: 'under-1000',
        budgetDisplay: '$45 - $65 / hr',
        duration: 'less-1-month',
        durationDisplay: 'Less than 1 Month',
        level: 'Intermediate',
        posted: '5 hours ago',
        company: 'NovaPay Global',
        location: 'Remote',
        skills: ['Figma', 'Mobile Design', 'Design Systems', 'Prototyping', 'iOS / Android'],
        desc: 'Seeking a talented product designer to audit and revitalize our mobile retail banking user flows. You will create modern, high-converting Figma component libraries and interactive prototypes.',
        responsibilities: [
          'Conduct usability review of existing money-transfer and bill-pay screens',
          'Build atomic design tokens for dark and light themes in Figma',
          'Deliver hand-off specifications for Flutter engineering team'
        ],
        proposalsCount: 12,
        suggestedRate: 55
      },
      {
        id: 'job-3',
        title: 'Full-Stack Cross-Platform Mobile Flutter App',
        category: 'mobile',
        budgetType: 'fixed',
        budget: '1000-3000',
        budgetDisplay: '$2,500 - $3,500',
        duration: '1-3-months',
        durationDisplay: '1 to 3 Months',
        level: 'Senior',
        posted: '1 day ago',
        company: 'Stride HealthTech',
        location: 'Remote',
        skills: ['Flutter', 'Dart', 'Spring Boot', 'WebSockets', 'HealthKit'],
        desc: 'Build an iOS and Android fitness tracking companion app that syncs wearable sensor readings in real-time to a secure cloud backend.',
        responsibilities: [
          'Implement Bluetooth LE background syncing for fitness trackers',
          'Design offline-first SQLite cache with automatic cloud reconciliation',
          'Connect with Spring Boot OAuth2 security and biometric login'
        ],
        proposalsCount: 9,
        suggestedRate: 2800
      },
      {
        id: 'job-4',
        title: 'Enterprise Headless E-Commerce Platform',
        category: 'web',
        budgetType: 'fixed',
        budget: '5000-plus',
        budgetDisplay: '$6,000 - $9,000',
        duration: 'more-3-months',
        durationDisplay: '3+ Months',
        level: 'Expert',
        posted: '2 days ago',
        company: 'Luxe Retail Group',
        location: 'Remote (Worldwide)',
        skills: ['Next.js', 'Spring Boot', 'PostgreSQL', 'Stripe', 'Redis'],
        desc: 'High-volume international e-commerce redesign with headless CMS, sub-second product catalogue search, and automated inventory sync with Spring Boot backend.',
        responsibilities: [
          'Architect Next.js storefront with dynamic server-side rendering',
          'Integrate multi-currency Stripe checkout and tax calculation APIs',
          'Collaborate with backend architects on inventory locking and order queues'
        ],
        proposalsCount: 14,
        suggestedRate: 7500
      }
    ],
    proposals: [
      {
        id: 'prop-1',
        jobId: 'job-1',
        jobTitle: 'Modern Fintech Dashboard in React & Spring Boot',
        clientName: 'Apex Capital Partners',
        proposedRate: 4500,
        estimatedDays: 28,
        status: 'Active',
        submittedAt: '2026-09-12T14:30:00Z',
        coverLetter: 'I have built multiple financial trading and analytics dashboards with React and Spring Boot. I can deliver clean TypeScript components with real-time websocket updates.'
      },
      {
        id: 'prop-2',
        jobId: 'job-3',
        jobTitle: 'Full-Stack Cross-Platform Mobile Flutter App',
        clientName: 'Stride HealthTech',
        proposedRate: 2800,
        estimatedDays: 45,
        status: 'Submitted',
        submittedAt: '2026-09-10T10:15:00Z',
        coverLetter: 'Extensive background in mobile architecture and offline-first data synchronization.'
      },
      {
        id: 'prop-3',
        jobId: 'job-2',
        jobTitle: 'Mobile Banking App UI/UX Redesign System',
        clientName: 'NovaPay Global',
        proposedRate: 60,
        estimatedDays: 14,
        status: 'Archived',
        submittedAt: '2026-08-28T09:00:00Z',
        coverLetter: 'Specialist in Figma design systems for financial applications.'
      }
    ],
    chatThreads: [
      {
        id: 'thread-1',
        name: 'David Miller',
        company: 'Apex Capital Partners',
        avatar: '../assets/images/avatar-david.png',
        role: 'Client',
        status: 'online',
        unread: 0,
        messages: [
          { sender: 'David Miller', text: 'Hi Sarah, we reviewed your proposal for the Fintech Dashboard and were impressed with your portfolio!', time: '10:30 AM', isMe: false },
          { sender: 'Sarah Jenkins', text: 'Thank you David! I would love to discuss your architecture and API specifications.', time: '10:32 AM', isMe: true },
          { sender: 'David Miller', text: 'Our backend is built with Spring Boot 3 and Java 21 exposing standard REST endpoints with OpenAPI. Can you handle the interactive Chart components?', time: '10:35 AM', isMe: false },
          { sender: 'Sarah Jenkins', text: 'Yes, absolutely. I regularly work with Chart.js and D3.js and can easily consume your Spring Boot endpoints.', time: '10:38 AM', isMe: true }
        ]
      },
      {
        id: 'thread-2',
        name: 'Elena Rostova',
        company: 'NovaPay Global',
        avatar: '../assets/images/avatar-elena.png',
        role: 'Design Director',
        status: 'offline',
        unread: 1,
        messages: [
          { sender: 'Elena Rostova', text: 'Hello Sarah, could you provide sample Figma links for your latest design system work?', time: 'Yesterday', isMe: false }
        ]
      },
      {
        id: 'thread-3',
        name: 'Marcus Vance',
        company: 'Stride HealthTech',
        avatar: '../assets/images/avatar-marcus.png',
        role: 'Tech Lead',
        status: 'offline',
        unread: 0,
        messages: [
          { sender: 'Marcus Vance', text: 'We have scheduled milestone 1 review for Monday morning. Please have the deliverable uploaded.', time: 'Sep 11', isMe: false },
          { sender: 'Sarah Jenkins', text: 'Got it Marcus! I will upload the zip and APK build through the work portal today.', time: 'Sep 11', isMe: true }
        ]
      }
    ],
    skillCategories: [
      { id: 'web', name: 'Web Development', icon: '💻', questionCount: 5, difficulty: 'Intermediate - Expert' },
      { id: 'uiux', name: 'UI / UX Design', icon: '🎨', questionCount: 5, difficulty: 'All Levels' },
      { id: 'mobile', name: 'Mobile App Development', icon: '📱', questionCount: 5, difficulty: 'Intermediate' },
      { id: 'cloud', name: 'Cloud & Spring Boot Architecture', icon: '☁️', questionCount: 5, difficulty: 'Advanced' }
    ],
    skillQuestions: {
      web: [
        {
          id: 1,
          question: 'In modern JavaScript and asynchronous programming, what does Promise.all() do when one of the input promises rejects?',
          options: [
            'It waits for all other promises to resolve before returning an error',
            'It immediately rejects with the reason of the first promise that rejected',
            'It ignores the rejected promise and returns only successful results',
            'It retries the failed promise up to 3 times automatically'
          ],
          correct: 1,
          explanation: 'Promise.all() rejects immediately upon any input promise rejecting (fail-fast behavior).'
        },
        {
          id: 2,
          question: 'When connecting a client frontend to a Spring Boot REST API on a different port or domain, which HTTP header/mechanism must the backend configure to allow browser requests?',
          options: [
            'Content-Security-Policy: sandbox',
            'CORS (Cross-Origin Resource Sharing) with Access-Control-Allow-Origin',
            'X-Frame-Options: SAMEORIGIN',
            'Strict-Transport-Security'
          ],
          correct: 1,
          explanation: 'Browsers enforce the Same-Origin Policy; Spring Boot uses @CrossOrigin or WebMvcConfigurer to provide CORS headers.'
        },
        {
          id: 3,
          question: 'Which HTTP method should be used according to RESTful standards to partially update fields of an existing resource?',
          options: ['PUT', 'POST', 'PATCH', 'UPDATE'],
          correct: 2,
          explanation: 'PATCH is intended for partial updates, whereas PUT is used for complete replacement of a resource.'
        },
        {
          id: 4,
          question: 'How do you prevent a browser form submission from causing a full page refresh in vanilla JavaScript?',
          options: [
            'form.stopRefresh()',
            'event.preventDefault()',
            'window.cancelSubmission()',
            'document.body.freeze()'
          ],
          correct: 1,
          explanation: 'event.preventDefault() stops the default browser form navigation behavior.'
        },
        {
          id: 5,
          question: 'In Spring Boot REST controllers, which annotation extracts query parameters from the request URL (e.g., /api/jobs?category=web)?',
          options: ['@PathVariable', '@RequestParam', '@RequestBody', '@RequestHeader'],
          correct: 1,
          explanation: '@RequestParam binds query parameters from the request URI.'
        }
      ]
    },
    freelancers: [
      {
        id: 'f-101',
        name: 'Sarah Jenkins',
        title: 'Senior Frontend & React Specialist',
        avatar: '../assets/images/avatar-sarah.png',
        hourlyRate: 65,
        rating: 4.95,
        score: 94,
        verifiedBadge: 'Verified Expert',
        category: 'web',
        skills: ['React', 'TypeScript', 'TailwindCSS', 'Spring Boot', 'REST APIs'],
        completedProjects: 34,
        bio: 'Full-stack frontend specialist experienced with scalable enterprise web applications.'
      },
      {
        id: 'f-102',
        name: 'Alex Rivera',
        title: 'Lead UI/UX Product Designer',
        avatar: '../assets/images/avatar-alex.png',
        hourlyRate: 70,
        rating: 4.88,
        score: 91,
        verifiedBadge: 'Verified Pro',
        category: 'uiux',
        skills: ['Figma', 'Design Systems', 'UX Research', 'Prototyping'],
        completedProjects: 48,
        bio: 'Award-winning product designer focused on mobile fintech and SaaS.'
      },
      {
        id: 'f-103',
        name: 'Tariq Hassan',
        title: 'Senior Mobile Engineer (Flutter & Android)',
        avatar: '../assets/images/avatar-tariq.png',
        hourlyRate: 55,
        rating: 4.92,
        score: 96,
        verifiedBadge: 'Verified Master',
        category: 'mobile',
        skills: ['Flutter', 'Dart', 'Kotlin', 'Firebase', 'REST APIs'],
        completedProjects: 29,
        bio: 'Specialist in high-performance cross-platform mobile apps with native bridges.'
      }
    ],
    portfolio: [
      {
        id: 'port-1',
        title: 'Cryptocurrency Trading Dashboard',
        category: 'Web Application',
        url: 'https://github.com/sarahjenkins/crypto-dash',
        image: '../assets/images/portfolio-1.png',
        desc: 'Real-time WebSocket and REST trading interface with interactive candlestick charts.'
      },
      {
        id: 'port-2',
        title: 'Hospitality Management SaaS',
        category: 'Full-Stack System',
        url: 'https://github.com/sarahjenkins/hospitality-app',
        image: '../assets/images/portfolio-2.png',
        desc: 'Booking calendar and room status management integrated with Spring Boot backend.'
      }
    ],
    clientProjects: [
      {
        id: 'proj-1',
        title: 'E-commerce Redesign and Migration',
        status: 'In Progress',
        budgetType: 'Fixed Price',
        budget: '$4,500',
        proposalsCount: 34,
        freelancer: 'Ahsan',
        postedDate: 'Jul 18, 2026',
        dueDate: 'Due in 14 days'
      },
      {
        id: 'proj-2',
        title: 'React Native Mobile App Development',
        status: 'Open',
        budgetType: 'Hourly',
        budget: '$45 - $65 / hr',
        proposalsCount: 12,
        freelancer: null,
        postedDate: 'Sep 7, 2026',
        dueDate: 'Reviewing candidates'
      },
      {
        id: 'proj-3',
        title: 'Brand Identity & Logo Design',
        status: 'Completed',
        budgetType: 'Fixed Price',
        budget: '$1,200',
        proposalsCount: 19,
        freelancer: 'Sadia',
        postedDate: 'Sep 20, 2023',
        dueDate: 'Finished Oct 12, 2023'
      },
      {
        id: 'proj-4',
        title: 'Python Data Scraping Script',
        status: 'Awaiting Approval',
        budgetType: 'Fixed Price',
        budget: '$450',
        proposalsCount: 8,
        freelancer: 'Moinul Islam',
        postedDate: 'Aug 22, 2026',
        dueDate: 'Milestone 1 Submitted'
      }
    ]
  };

  // Local Store Manager
  class MockStore {
    constructor() {
      this.init();
    }

    init() {
      const stored = localStorage.getItem(CONFIG.STORAGE_KEY);
      if (!stored) {
        this.data = JSON.parse(JSON.stringify(INITIAL_MOCK_DATA));
        this.save();
      } else {
        try {
          this.data = JSON.parse(stored);
        } catch (e) {
          this.data = JSON.parse(JSON.stringify(INITIAL_MOCK_DATA));
          this.save();
        }
      }
    }

    save() {
      localStorage.setItem(CONFIG.STORAGE_KEY, JSON.stringify(this.data));
    }

    reset() {
      this.data = JSON.parse(JSON.stringify(INITIAL_MOCK_DATA));
      this.save();
    }
  }

  const store = new MockStore();

  // Authentication Manager
  const AuthManager = {
    getUser() {
      const sess = localStorage.getItem(CONFIG.SESSION_KEY);
      if (sess) {
        try {
          return JSON.parse(sess);
        } catch (e) { }
      }
      return store.data.currentUser;
    },

    setUser(user, token = 'jwt-mock-session-token') {
      const session = { ...user, token, lastLogin: new Date().toISOString() };
      localStorage.setItem(CONFIG.SESSION_KEY, JSON.stringify(session));
      store.data.currentUser = user;
      store.save();
      return session;
    },

    logout() {
      localStorage.removeItem(CONFIG.SESSION_KEY);
      window.location.href = '../guest/login.html';
    },

    isAuthenticated() {
      return !!this.getUser();
    },

    getRole() {
      const u = this.getUser();
      return u ? u.role : 'GUEST';
    }
  };

  // Unified API Client with Automatic Fallback
  const apiClient = {
    baseUrl: CONFIG.BASE_URL,

    async request(endpoint, options = {}) {
      const url = `${this.baseUrl}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
      const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
      };

      const user = AuthManager.getUser();
      if (user && user.token) {
        headers['Authorization'] = `Bearer ${user.token}`;
      }

      const fetchOptions = {
        ...options,
        headers
      };

      try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), CONFIG.TIMEOUT);
        fetchOptions.signal = controller.signal;

        const response = await fetch(url, fetchOptions);
        clearTimeout(timeoutId);

        if (!response.ok) {
          const errData = await response.json().catch(() => ({}));
          throw new Error(errData.message || `HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
      } catch (err) {
        // When Spring Boot backend is offline or unreachable, fall back to mock store
        console.warn(`[SkillMatch API] Backend request to ${endpoint} failed (${err.message}). Falling back to local dynamic store.`);
        return this.mockHandler(endpoint, options);
      }
    },

    get(endpoint, params = {}) {
      const query = new URLSearchParams(params).toString();
      const path = query ? `${endpoint}?${query}` : endpoint;
      return this.request(path, { method: 'GET' });
    },

    post(endpoint, body) {
      return this.request(endpoint, {
        method: 'POST',
        body: JSON.stringify(body)
      });
    },

    put(endpoint, body) {
      return this.request(endpoint, {
        method: 'PUT',
        body: JSON.stringify(body)
      });
    },

    delete(endpoint) {
      return this.request(endpoint, { method: 'DELETE' });
    },

    // Mock handler to allow dynamic rendering before Spring Boot is launched
    mockHandler(endpoint, options) {
      const [path, queryStr] = endpoint.split('?');
      const params = new URLSearchParams(queryStr || '');
      const method = (options.method || 'GET').toUpperCase();
      const body = options.body ? JSON.parse(options.body) : {};

      return new Promise((resolve, reject) => {
        setTimeout(() => {
          // --- Auth Endpoints ---
          if (path === '/auth/login' && method === 'POST') {
            const { email, role } = body;
            const user = {
              ...store.data.currentUser,
              email: email || store.data.currentUser.email,
              role: role || store.data.currentUser.role
            };
            AuthManager.setUser(user);
            return resolve({ token: 'jwt-mock-token-' + Date.now(), user });
          }

          // --- Jobs Endpoints ---
          if (path === '/jobs' && method === 'GET') {
            let jobs = [...store.data.jobs];
            const cat = params.get('category');
            const budget = params.get('budget');
            const duration = params.get('duration');
            const search = (params.get('search') || '').toLowerCase();

            if (cat && cat !== 'all') {
              const cats = cat.split(',');
              jobs = jobs.filter(j => cats.includes(j.category));
            }
            if (budget && budget !== 'any') {
              jobs = jobs.filter(j => j.budget === budget);
            }
            if (duration && duration !== 'any') {
              jobs = jobs.filter(j => j.duration === duration);
            }
            if (search) {
              jobs = jobs.filter(j =>
                j.title.toLowerCase().includes(search) ||
                j.desc.toLowerCase().includes(search) ||
                j.skills.some(s => s.toLowerCase().includes(search))
              );
            }
            return resolve(jobs);
          }

          const jobDetailMatch = path.match(/^\/jobs\/([a-zA-Z0-9-]+)$/);
          if (jobDetailMatch && method === 'GET') {
            const job = store.data.jobs.find(j => j.id === jobDetailMatch[1]);
            return job ? resolve(job) : reject(new Error('Job not found'));
          }

          const jobProposalMatch = path.match(/^\/jobs\/([a-zA-Z0-9-]+)\/proposals$/);
          if (jobProposalMatch && method === 'POST') {
            const jobId = jobProposalMatch[1];
            const job = store.data.jobs.find(j => j.id === jobId);
            const newProposal = {
              id: 'prop-' + (store.data.proposals.length + 1),
              jobId,
              jobTitle: job ? job.title : 'Project',
              clientName: job ? job.company : 'Client',
              proposedRate: Number(body.proposedRate) || 3000,
              estimatedDays: Number(body.estimatedDays) || 14,
              coverLetter: body.coverLetter || '',
              status: 'Submitted',
              submittedAt: new Date().toISOString()
            };
            store.data.proposals.unshift(newProposal);
            store.save();
            return resolve(newProposal);
          }

          // --- Proposals Endpoints ---
          if (path === '/freelancers/me/proposals' && method === 'GET') {
            const filter = params.get('status');
            let props = store.data.proposals;
            if (filter && filter !== 'all') {
              props = props.filter(p => p.status.toLowerCase() === filter.toLowerCase());
            }
            return resolve(props);
          }

          const withdrawMatch = path.match(/^\/proposals\/([a-zA-Z0-9-]+)$/);
          if (withdrawMatch && method === 'DELETE') {
            const propId = withdrawMatch[1];
            store.data.proposals = store.data.proposals.filter(p => p.id !== propId);
            store.save();
            return resolve({ success: true, message: 'Proposal withdrawn successfully' });
          }

          // --- Skills Endpoints ---
          if (path === '/skills/categories' && method === 'GET') {
            return resolve(store.data.skillCategories);
          }

          const skillTestMatch = path.match(/^\/skills\/tests\/([a-zA-Z0-9-]+)$/);
          if (skillTestMatch && method === 'GET') {
            const category = skillTestMatch[1];
            const questions = store.data.skillQuestions[category] || store.data.skillQuestions['web'];
            return resolve(questions);
          }

          const skillSubmitMatch = path.match(/^\/skills\/tests\/([a-zA-Z0-9-]+)\/submit$/);
          if (skillSubmitMatch && method === 'POST') {
            const answers = body.answers || {};
            const category = skillSubmitMatch[1];
            const questions = store.data.skillQuestions[category] || store.data.skillQuestions['web'];
            let correct = 0;
            questions.forEach((q, idx) => {
              if (Number(answers[q.id]) === q.correct) {
                correct++;
              }
            });
            const score = Math.round((correct / questions.length) * 100);
            const passed = score >= 70;
            const result = {
              category,
              score,
              passed,
              correctCount: correct,
              totalCount: questions.length,
              verifiedBadge: passed ? 'Verified Pro' : null,
              submittedAt: new Date().toISOString()
            };
            sessionStorage.setItem('skillmatch_latest_test_result', JSON.stringify(result));
            return resolve(result);
          }

          if (path === '/skills/tests/results/latest' && method === 'GET') {
            const stored = sessionStorage.getItem('skillmatch_latest_test_result');
            if (stored) {
              return resolve(JSON.parse(stored));
            }
            return resolve({
              category: 'web',
              score: 88,
              passed: true,
              correctCount: 4,
              totalCount: 5,
              verifiedBadge: 'Verified Pro',
              submittedAt: new Date().toISOString()
            });
          }

          // --- Chat Endpoints ---
          if (path === '/chat/threads' && method === 'GET') {
            return resolve(store.data.chatThreads);
          }

          const chatMessagesMatch = path.match(/^\/chat\/threads\/([a-zA-Z0-9-]+)\/messages$/);
          if (chatMessagesMatch) {
            const threadId = chatMessagesMatch[1];
            const thread = store.data.chatThreads.find(t => t.id === threadId);
            if (!thread) return reject(new Error('Thread not found'));

            if (method === 'GET') {
              return resolve(thread.messages);
            }
            if (method === 'POST') {
              const newMsg = {
                sender: store.data.currentUser.name,
                text: body.text,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isMe: true
              };
              thread.messages.push(newMsg);
              thread.lastMessage = body.text;
              store.save();
              return resolve(newMsg);
            }
          }

          // --- Freelancers Endpoints ---
          if (path === '/freelancers' && method === 'GET') {
            return resolve(store.data.freelancers);
          }

          const freelancerDetailMatch = path.match(/^\/freelancers\/([a-zA-Z0-9-]+)$/);
          if (freelancerDetailMatch && method === 'GET') {
            const fId = freelancerDetailMatch[1];
            const found = store.data.freelancers.find(f => f.id === fId);
            return resolve(found || store.data.freelancers[0]);
          }

          if (path === '/freelancers/me' && method === 'GET') {
            return resolve(store.data.currentUser);
          }

          if (path === '/freelancers/me' && method === 'PUT') {
            store.data.currentUser = { ...store.data.currentUser, ...body };
            store.save();
            return resolve(store.data.currentUser);
          }

          if (path === '/freelancers/me/portfolio' && method === 'GET') {
            return resolve(store.data.portfolio);
          }

          if (path === '/freelancers/me/portfolio' && method === 'POST') {
            const newItem = {
              id: 'port-' + (store.data.portfolio.length + 1),
              title: body.title || 'New Project',
              category: body.category || 'Web Application',
              url: body.url || '#',
              image: body.image || '../assets/images/portfolio-1.png',
              desc: body.desc || ''
            };
            store.data.portfolio.unshift(newItem);
            store.save();
            return resolve(newItem);
          }

          // --- Client Projects & Dashboard Endpoints ---
          if (path === '/clients/me/dashboard' && method === 'GET') {
            const projects = store.data.clientProjects || [];
            return resolve({
              clientName: 'Abida Hasan',
              activeProjects: projects.filter(p => p.status === 'In Progress' || p.status === 'Open').length,
              proposalsReceived: 12,
              freelancersHired: 8,
              totalSpent: '$12,450'
            });
          }

          if (path === '/clients/me/projects' && method === 'GET') {
            const statusFilter = params.get('status');
            let projects = store.data.clientProjects || [];
            if (statusFilter && statusFilter.toLowerCase() !== 'all') {
              projects = projects.filter(p => p.status.toLowerCase() === statusFilter.toLowerCase());
            }
            return resolve(projects);
          }

          if (path === '/jobs' && method === 'POST') {
            const newJob = {
              id: 'job-' + (store.data.jobs.length + 1),
              title: body.title || 'Untitled Project',
              category: body.category || 'web',
              budgetType: body.budgetType || 'fixed',
              budget: body.budget || '1000-3000',
              budgetDisplay: body.budgetDisplay || '$' + (body.budget || '3,000'),
              duration: body.duration || '1-3-months',
              durationDisplay: body.durationDisplay || '1-3 Months',
              level: body.level || 'Intermediate',
              posted: 'Just now',
              company: 'Abida Hasan (Client)',
              location: 'Remote',
              skills: Array.isArray(body.skills) ? body.skills : ['React', 'TypeScript'],
              desc: body.desc || 'No description provided',
              responsibilities: body.responsibilities || ['Deliver required project milestones'],
              proposalsCount: 0,
              suggestedRate: 3000
            };
            store.data.jobs.unshift(newJob);
            if (!store.data.clientProjects) store.data.clientProjects = [];
            store.data.clientProjects.unshift({
              id: 'proj-' + (store.data.clientProjects.length + 1),
              title: newJob.title,
              status: 'Open',
              budgetType: newJob.budgetType === 'fixed' ? 'Fixed Price' : 'Hourly',
              budget: newJob.budgetDisplay,
              proposalsCount: 0,
              freelancer: null,
              postedDate: 'Today',
              dueDate: 'Pending'
            });
            store.save();
            return resolve(newJob);
          }

          // Default fallback
          return resolve({ success: true, message: 'Mock response for ' + path });
        }, 150);
      });
    }
  };

  // Toast notification helper
  function showToast(message, type = 'success', duration = 3500) {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.innerHTML = `
      <span>${message}</span>
      <button type="button" style="background:none;border:none;color:inherit;font-size:16px;cursor:pointer;margin-left:12px;" aria-label="Close">&times;</button>
    `;

    const closeBtn = toast.querySelector('button');
    closeBtn.addEventListener('click', () => toast.remove());

    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  // Expose globally
  window.SkillMatch = {
    config: CONFIG,
    store,
    auth: AuthManager,
    api: apiClient,
    showToast
  };

})();
