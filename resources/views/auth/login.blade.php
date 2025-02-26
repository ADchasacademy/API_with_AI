<x-layout class="flex max-w-xl flex-col gap-12 pt-4 md:pt-20">
    <form method="post" action="{{ route('login') }}" class="flex flex-col gap-8">
        @csrf

        <div class="flex text-black flex-col gap-4">
            <input type="text" name="username" placeholder="Your username" required/>
            <input type="password" name="password" placeholder="Your password" required/>
        </div>

        <button type="submit">Submit</button>
    </form>
</x-layout>
