@extends('layouts.app')

@section('title', 'Contact | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                Get in Touch
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                We're here to help with your questions about vehicles,
                financing, and our services. Reach out to us anytime.
            </p>
        </div>
    </section>

    <!-- Contact Form and Details Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
                <!-- Contact Form -->
                <div class="rounded-lg border border-border bg-card">
                    <div class="p-6">
                        <h2 class="text-2xl font-semibold tracking-tight">Send Us a Message</h2>
                        <p class="text-muted-foreground mt-2">
                            Fill out the form below, and we'll get back to you as
                            soon as possible.
                        </p>
                    </div>
                    <div class="p-6 pt-0">
                        <form class="space-y-6" method="POST" action="#">
                            @csrf
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Full Name</label>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        placeholder="Enter your full name"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Email Address</label>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        placeholder="Enter your email"
                                        required
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="subject" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Subject</label>
                                <select
                                    id="subject"
                                    name="subject"
                                    class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="">Select a subject</option>
                                    <option value="vehicle-inquiry">Vehicle Inquiry</option>
                                    <option value="financing">Financing Question</option>
                                    <option value="service-appointment">Service Appointment</option>
                                    <option value="general">General Question</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="message" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Message</label>
                                <textarea
                                    id="message"
                                    name="message"
                                    placeholder="Write your message here..."
                                    rows="6"
                                    required
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                ></textarea>
                            </div>
                            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 h-4 w-4">
                                    <path d="m22 2-7 20-4-9-9-4Z"></path>
                                    <path d="M22 2 11 13"></path>
                                </svg>
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Contact Details -->
                <div class="space-y-8">
                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold">Contact Information</h2>
                        <p class="text-muted-foreground">
                            Find us at our dealership or reach out via phone or email.
                        </p>
                    </div>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <path d="M20 10c0 6-1 9-9 9s-9-3-9-9 1-9 9-9 9 3 9 9Z"></path>
                                    <path d="M20 10c0 3.866-4 7-9 7s-9-3.134-9-7 4-7 9-7 9 3.134 9 7Z"></path>
                                    <path d="M6 10h12"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">Our Address</h3>
                                <p class="text-muted-foreground">
                                    123 Dealership Lane, Kerala, India
                                </p>
                                <a
                                    href="#"
                                    class="text-primary mt-1 inline-block text-sm font-medium hover:underline"
                                >
                                    Get Directions
                                </a>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92Z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">Phone</h3>
                                <p class="text-muted-foreground">
                                    +91-123-456-7890
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <rect width="20" height="16" x="2" y="4" rx="2"></rect>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">Email</h3>
                                <p class="text-muted-foreground">
                                    info@bilskyen.dk
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="bg-primary/10 text-primary flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold">Business Hours</h3>
                                <p class="text-muted-foreground">
                                    Monday - Saturday: 9:00 AM - 7:00 PM
                                </p>
                                <p class="text-muted-foreground">
                                    Sunday: 10:00 AM - 5:00 PM
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="bg-muted">
        <div class="relative h-96 w-full">
            <img
                src="/images/showroom.jpg"
                alt="Showroom"
                class="h-full w-full object-cover"
                onerror="this.src='https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'"
            />
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center text-white">
                <h2 class="text-3xl font-bold">Visit Our Showroom</h2>
                <p class="mt-2 max-w-md">123 Dealership Lane, Kerala, India</p>
            </div>
        </div>
    </section>
</div>
@endsection

