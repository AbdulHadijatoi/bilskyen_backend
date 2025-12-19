@extends('layouts.auth')

@section('title', 'Magic Link Login - RevoLot')

@section('content')
<div class="flex h-full w-full flex-col items-center justify-center gap-4">
    <div class="flex w-full flex-col space-y-2">
        <h1 class="text-2xl font-semibold tracking-tight">
            Magic Link Login
        </h1>
        <p class="text-sm text-muted-foreground">
            Enter your email address and we'll send you a magic link to login.
        </p>
    </div>

    <form class="grid w-full gap-3.5" onsubmit="event.preventDefault(); handleSubmit();">
        <div class="grid gap-2">
            <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Email</label>
            <input id="email" name="email" type="email" placeholder="johndoe@mail.com" autocomplete="email" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
        </div>

        <button type="submit" id="submitBtn" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
            Send Magic Link
        </button>
    </form>

    <div class="mt-4 text-center text-sm">
        Already have an account? <a href="/auth/login" class="underline">Login with password</a>
    </div>
</div>

<script>
let timer = 0;
const submitBtn = document.getElementById('submitBtn');

function handleSubmit() {
    timer = 30;
    submitBtn.disabled = true;
    const interval = setInterval(() => {
        timer--;
        if (timer > 0) {
            submitBtn.textContent = `Resend in ${timer}s`;
        } else {
            clearInterval(interval);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Magic Link';
        }
    }, 1000);
    alert('We\'ve sent you a magic link! Check your inbox to login.');
}

document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    handleSubmit();
});
</script>
@endsection

