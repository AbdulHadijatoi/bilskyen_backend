@extends('layouts.app')

@section('title', 'About Bilskyen | Bilskyen')

@section('content')
<div class="flex min-h-screen flex-col">
    <!-- Page Header Section -->
    <section class="bg-muted py-20 text-center">
        <div class="container mx-auto px-4 md:px-6">
            <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                About Bilskyen
            </h1>
            <p class="text-muted-foreground mx-auto mt-4 max-w-2xl text-lg">
                We are dedicated to revolutionizing the dealership industry with
                cutting-edge technology and a passion for excellence.
            </p>
        </div>
    </section>

    <!-- Mission and Story Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="grid items-center gap-12 md:grid-cols-2">
                <div class="space-y-6">
                    <div class="text-primary flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                            <path d="M4.5 16.5c-1.5 1.5-3 3-2 5s3.5-.5 5-2c1.5-1.5 2.5-3.5 2-5s-3.5-1.5-5 2z"></path>
                            <path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"></path>
                            <path d="M20 8v6"></path>
                            <path d="M17 11h6"></path>
                        </svg>
                        <h2 class="text-sm font-semibold tracking-wider uppercase">
                            Our Mission
                        </h2>
                    </div>
                    <h3 class="text-3xl font-bold tracking-tight">
                        Empowering Dealerships Through Innovation
                    </h3>
                    <p class="text-muted-foreground">
                        Bilskyen was born from a simple idea: to create a dealership
                        management system that is powerful, intuitive, and affordable.
                        We saw the challenges dealerships faced with outdated, complex
                        software and knew there was a better way.
                    </p>
                    <p class="text-muted-foreground">
                        Our mission is to provide a comprehensive, all-in-one platform
                        that streamlines every aspect of dealership operations, from
                        inventory management and sales to accounting and customer
                        relations. We empower owners and staff to work smarter, not
                        harder, freeing them to focus on what truly matters: building
                        relationships and growing their business.
                    </p>
                </div>
                <div class="relative h-80 w-full overflow-hidden rounded-lg shadow-lg">
                    <img
                        src="/images/showroom.jpg"
                        alt="Modern car dealership interior"
                        class="h-full w-full object-cover"
                        onerror="this.src='https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800&h=600&fit=crop'"
                    />
                </div>
            </div>
        </div>
    </section>

    <div class="border-t border-border"></div>

    <!-- Our Values Section -->
    <section class="bg-muted py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold tracking-tight">Our Values</h2>
                <p class="text-muted-foreground mx-auto mt-2 max-w-2xl">
                    The principles that guide every decision we make.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M9 2v6l6-3-6-3z"></path>
                            <path d="M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
                            <path d="M20 13a8 8 0 1 1-16 0 8 8 0 0 1 16 0z"></path>
                            <path d="M12 23a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Innovation</h3>
                    <p class="text-muted-foreground mt-1">
                        We constantly push the boundaries of what's possible, integrating the latest technology to solve real-world dealership challenges.
                    </p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1 1 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Transparency</h3>
                    <p class="text-muted-foreground mt-1">
                        We believe in honest, clear communication and pricing. What you see is what you get, from our software to our support.
                    </p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Customer-Centricity</h3>
                    <p class="text-muted-foreground mt-1">
                        Our customers are our partners. We succeed when they succeed, and we're dedicated to providing exceptional support and value.
                    </p>
                </div>
                <div class="flex flex-col items-center text-center">
                    <div class="bg-primary/10 mb-4 flex h-14 w-14 items-center justify-center rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary h-7 w-7">
                            <path d="M7 10v12"></path>
                            <path d="m15 5-2-2-2 2"></path>
                            <path d="M17 14v-3a2 2 0 0 0-2-2h-2"></path>
                            <path d="M17 14H7"></path>
                            <path d="M7 14v-3a2 2 0 0 1 2-2h2"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold">Integrity</h3>
                    <p class="text-muted-foreground mt-1">
                        We operate with the highest ethical standards, building trust through reliability, honesty, and a commitment to excellence.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Meet the Team Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 md:px-6">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold tracking-tight">Meet the Team</h2>
                <p class="text-muted-foreground mx-auto mt-2 max-w-2xl">
                    The passionate individuals driving Bilskyen forward.
                </p>
            </div>
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <div class="bg-muted/50 p-5 text-center">
                        <div class="border-background mx-auto h-24 w-24 rounded-full border-4 bg-muted flex items-center justify-center">
                            <img src="https://github.com/Muhammed-Rahif.png" alt="Muhammed Rahif" class="h-full w-full rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-lg font-semibold\'>MR</span>'">
                        </div>
                        <div class="pt-4">
                            <h3 class="text-xl font-semibold">Muhammed Rahif</h3>
                            <p class="text-primary text-sm">Founder & CEO</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-muted-foreground">
                            With a passion for technology and the automotive industry, Muhammed founded Bilskyen to bring modern, efficient solutions to dealerships.
                        </p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <div class="bg-muted/50 p-5 text-center">
                        <div class="border-background mx-auto h-24 w-24 rounded-full border-4 bg-muted flex items-center justify-center">
                            <img src="/testimonials/person2.jpg" alt="Jane Doe" class="h-full w-full rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-lg font-semibold\'>JD</span>'">
                        </div>
                        <div class="pt-4">
                            <h3 class="text-xl font-semibold">Jane Doe</h3>
                            <p class="text-primary text-sm">Lead Developer</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-muted-foreground">
                            Jane is the architect behind our robust platform, ensuring a seamless and powerful user experience.
                        </p>
                    </div>
                </div>
                <div class="rounded-lg border border-border bg-card overflow-hidden">
                    <div class="bg-muted/50 p-5 text-center">
                        <div class="border-background mx-auto h-24 w-24 rounded-full border-4 bg-muted flex items-center justify-center">
                            <img src="/testimonials/person1.jpg" alt="John Smith" class="h-full w-full rounded-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-lg font-semibold\'>JS</span>'">
                        </div>
                        <div class="pt-4">
                            <h3 class="text-xl font-semibold">John Smith</h3>
                            <p class="text-primary text-sm">Head of Sales</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-muted-foreground">
                            John connects with our clients, understanding their needs and helping them leverage Bilskyen for maximum growth.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary text-primary-foreground py-16">
        <div class="container mx-auto px-4 md:px-6 text-center">
            <h2 class="text-3xl font-bold">Join the Revolution</h2>
            <p class="text-primary-foreground/90 mx-auto mt-4 max-w-2xl text-lg">
                Ready to see how Bilskyen can transform your dealership? Explore our
                features or get in touch with our team today.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                <a href="/vehicles" class="inline-flex h-10 items-center justify-center rounded-md bg-secondary px-8 text-sm font-medium text-secondary-foreground shadow-sm transition-colors hover:bg-secondary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    Explore Vehicles
                </a>
                <a href="/contact" class="inline-flex h-10 items-center justify-center rounded-md border border-primary-foreground bg-transparent px-8 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary-foreground hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
                    Contact Us
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

