<?php

namespace Tommica\Mailpox\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Tommica\Mailpox\Contracts\MessageStore;

final class MessageController extends Controller
{
    public function __construct(private MessageStore $store) {}

    public function index(Request $request): View
    {
        return view('mailpox::messages.index', [
            'messages' => $this->store->paginate($request->query('search'), (int) config('mailpox.per_page', 50))->withQueryString(),
            'selected' => null,
            'search' => $request->query('search', ''),
        ]);
    }

    public function show(Request $request, string $message): View
    {
        $selected = $this->store->find($message);
        abort_if($selected === null, 404);
        $this->store->markRead($message);

        return view('mailpox::messages.index', [
            'messages' => $this->store->paginate($request->query('search'), (int) config('mailpox.per_page', 50))->withQueryString(),
            'selected' => $selected,
            'search' => $request->query('search', ''),
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $messages = $this->store->paginate($request->query('search'), (int) config('mailpox.per_page', 50))->withQueryString();
        $selectedId = $request->query('selected');
        $selected = $selectedId ? $this->store->find($selectedId) : null;

        return response()->json([
            'count' => $messages->total(),
            'html' => view('mailpox::messages._rows', [
                'messages' => $messages,
                'selected' => $selected,
                'search' => $request->query('search', ''),
            ])->render(),
        ]);
    }

    public function html(string $message): Response
    {
        $selected = $this->store->find($message);
        abort_if($selected === null, 404);

        return response($selected->html ?? '', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => "default-src 'none'; img-src data: cid:; style-src 'unsafe-inline'; font-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function attachment(string $message, string $attachment): Response
    {
        $data = $this->store->attachment($message, $attachment);
        abort_if($data === null, 404);

        return response($data['content'], 200, [
            'Content-Type' => $data['content_type'],
            'Content-Disposition' => 'attachment; filename="'.addcslashes(basename($data['filename']), '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(string $message): RedirectResponse
    {
        $this->store->delete($message);

        return redirect()->route('mailpox.index')->with('mailpox_status', 'Message deleted.');
    }

    public function destroyAll(): RedirectResponse
    {
        $count = $this->store->deleteAll();

        return redirect()->route('mailpox.index')->with('mailpox_status', $count.' messages deleted.');
    }
}
