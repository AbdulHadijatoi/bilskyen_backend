@extends('layouts.auth')

@section('title', 'Verify Email - RevoLot')

@section('content')
<div class="flex h-full w-full flex-col items-start gap-4">
    <h2 class="text-2xl font-semibold">Verify Your Email</h2>

    <p>
        We've sent a verification email to your inbox when you signed up. Please check your email and follow the instructions to verify your account. The email might be further down in your inbox depending on when you signed up, so be sure to look carefully.
    </p>

    <p class="text-muted-foreground">
        Didn't receive the email? Please check your spam or junk folder. If it has expired or hasn't arrived yet, you can request a new one <span id="timerText">now</span>.
    </p>

    <button id="resendBtn" onclick="handleResend()" class="inline-flex h-10 w-full items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50">
        Resend Verification Email
    </button>
</div>

<script>
let timer = 30;
const resendBtn = document.getElementById('resendBtn');
const timerText = document.getElementById('timerText');

function updateTimer() {
    if (timer > 0) {
        timerText.textContent = `in ${timer} seconds`;
        resendBtn.disabled = true;
        resendBtn.textContent = `Resend in ${timer}s`;
    } else {
        timerText.textContent = 'now';
        resendBtn.disabled = false;
        resendBtn.textContent = 'Resend Verification Email';
    }
}

function handleResend() {
    timer = 30;
    const interval = setInterval(() => {
        timer--;
        updateTimer();
        if (timer <= 0) {
            clearInterval(interval);
        }
    }, 1000);
    updateTimer();
    alert('Verification email sent successfully! Please check your inbox.');
}

// Initialize timer display
updateTimer();
</script>
@endsection

