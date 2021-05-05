<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use App\Services\TodoService;
use App\Constants\TranslationCode;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\TaskLog;
use App\Models\Todo;
use App\Models\Agenda;
use App\Models\Action;
use Illuminate\Support\Facades\Auth;
use App\Repositories\TaskMediaRepository;
use App\Repositories\AttachmentRepository; //

use Illuminate\Http\Request;

use Response;

/**
 * Class Taskontroller
 * @package App\Http\Controllers
 */

class TaskController extends Controller
{
    private $taskRepository;

    private $taskService;
    private $todoService;


    public function __construct(TaskRepository $taskRepo, TaskMediaRepository $taskMediaRepo , AttachmentRepository $attachmmentRepo)
    {
        $this->taskRepository = $taskRepo;

        $this->taskMediaRepository = $taskMediaRepo;
        $this->AttachmentRepository = $attachmmentRepo ;

        $this->taskService = new TaskService();
        $this->todoService = new TodoService();
    }


    /**
     * Show Tasks list
     * GET /tasks
     * @return Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $relations = $this->taskRepository->getRelations('list');
        $tasks = $this->taskRepository->with($relations)->all(
            $this->taskRepository->prepareIndexSearchFields($request, $user),
            null,
            null,
            '*'
        );

        // $tasks = $this->taskRepository->with('medias','creator')->all();

        /*
        $actions = $this->actionRepository->with($relations)->all(
            $this->actionRepository->prepareIndexSearchFields($request, $user), 
            null, 
            null, 
            $columns
        );

        $tasks = $this->taskRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );
        */
        $hasAccess = $this->taskService->hasListAccess($user->id, Permission::TASK_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($tasks, 'Tasks retrieved successfully');
    }

    /**
     * Post Task.
     * POST /tasks
     * @return Response
     * @bodyParam meeting_id int  The  ID of the Meeting. Example: 1
     * @bodyParam action_id int  The  ID of the Action. Example: 1
     * @bodyParam todo_id int required The  ID of the Todo. Example: 1
     * @bodyParam title text required The  title of the Task. Example:  title
     * @bodyParam due_date date required The  due_date of the task. Example:  2020/07/03
     * @bodyParam start_date date required The  end_at of the task. Example:  2020/07/03
     * @bodyParam end_at date required The  end_at of the task. Example:  2020/07/03
     * @bodyParam content text required The  content of the Task. Example:  content
     * @bodyParam assignee_id int  The  id of the assignee. Example:  1
     * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $hasAccess = $this->taskService->hasCreateAccess($user->id, Permission::TASK_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        $validator = $this->taskService->validateCreateRequest($request);

        if (!$validator->passes()) {
            return $this->userErrorResponse($validator->messages()->toArray());
        }

        $input = $request->all();

        $input['creator_id'] = $user->id;


        $input['account_id'] = $user->account_id;

        if(!$request->todo_id && (isset($input['action_id']) || isset($input['agenda_id']))) {
            $todoData = [
                'creator_id' => $user->id,
                'account_id' => $user->account_id,
                'assignee_id' => ($input['assignee_id']) ? $input['assignee_id'] : null,
                'due_date' => ($input['due_date']) ? $input['due_date'] : null
            ];
            if(isset($input['action_id'])) {
                $todo = Todo::where('action_id', $input['action_id'])->first();
                $action = Action::find($input['action_id']);
                if(!$todo) {
                    $todoData['action_id'] = $input['action_id'];
                    $todoData['title'] = $action->title;
                }
            }

            if(isset($input['agenda_id'])) {
                $todo = Todo::where('agenda_id', $input['agenda_id'])->first();
                $agenda = Agenda::find($input['agenda_id']);
                if(!$todo) {
                    $todoData['title'] = $agenda->title;
                    $todoData['agenda_id'] = $input['agenda_id'];
                }
            }
            if(!$todo) {
                $todo = Todo::create($todoData);
            }
            $request->merge(['todo_id' => $todo->id]);
            $input['todo_id'] = $todo->id;
        }

        $task = $this->taskRepository->create($input);
        $task = $this->taskRepository->with('assignee.translation')->find($task->id);

        if (isset($input['attachments']) && !empty($input['attachments'])) {
            foreach ($input['attachments'] as $key => $attachment) {

                $validator = $this->taskService->validateAttachmentsRequest($request, $key);

                if (!$validator->passes()) {
                    return $this->userErrorResponse($validator->messages()->toArray());
                }

                $attachment['task_id'] = $task->id;
                $attachment['media_id'] = $attachment['media_id'];
                $attachment['title'] = $attachment['title'];
                $attachment['creator_id']= $user->id;
                $attachment['account_id']= $user->account_id;
                $attachment = $this->AttachmentRepository->create($attachment);
            }
        }
        $emailLink = '';
        if (isset($input['link']) && !empty($input['link'])) {
            $emailLink = $input['link'] . '/tasks/all';
        }
        $this->taskService->notifyAssigneeWithNewTask($task, $emailLink);
        if ($request->todo_id !== null) {
            $progress = $this->todoService->calculateProgress($request->todo_id);
        }

        return $this->sendResponse($task->toArray(), 'Task saved successfully');
    }

    /**
     * Show the specified Task.
     * GET /tasks/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Task.
     */
    public function show($id)
    {
        $user = Auth::user();

        $relations = $this->taskRepository->getRelations('item');
        $task = $this->taskRepository->with($relations)->find($id);

        if (empty($task)) {
            return $this->sendError('Task not found');
        }

        $hasAccess = $this->taskService->hasReadAccess($user->id, $task->creator_id, Permission::TASK_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        return $this->sendResponse($task->toArray(), 'Task retrieved successfully');
    }

    /**
     * Update the specified Task.
     * PUT/PATCH /tasks/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Attachment.
     * @bodyParam meeting_id int  The  ID of the task. Example: 1
     * @bodyParam action_id int  The  ID of the Action. Example: 1
     * @bodyParam todo_id int required The  ID of the Todo. Example: 1
     * @bodyParam title text required The  title of the Task. Example:  title
     * @bodyParam due_date date required The  due_date of the task. Example:  2020/07/03
     * @bodyParam start_date date required The  end_at of the task. Example:  2020/07/03
     * @bodyParam end_at date required The  end_at of the task. Example:  2020/07/03
     * @bodyParam content text required The  content of the Task. Example:  content
    

     * @bodyParam assignee_id int  The  id of the assignee. Example:  1
     * @bodyParam attachments[0][media_id] file required The  media id  of the Media Id member . Example:1 
     */
    public function update($id, Request $request)
    {
        $user = Auth::user();

        $input = $request->all();

        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            return $this->sendError('Task not found');
        }

        $hasAccess = $this->taskService->hasUpdateAccess($user->id, $task->creator_id, Permission::TASK_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }

        // if (isset($input['content'])) {
        //          // check if log already added
        // $taskLog = array('task_id' => $id, 'assignee_id' => $user->id, 'task_status' => $input['status'], 'status' => TaskLog::UPDATE_STATUS);
        // TaskLog::create($taskLog);
        // }
    
            // check if log already added
   $taskLog = array('task_id' => $id, 'assignee_id' => $user->id, 'task_status' => $input['status'], 'status' => TaskLog::UPDATE_STATUS);
   TaskLog::create($taskLog);
  

        $task = $this->taskRepository->update($input, $id);

        $task = $this->taskRepository->with('assignee.translation')->find($task->id);


        if (isset($input['attachments']) && !empty($input['attachments'])) {
            foreach ($input['attachments'] as $key => $attachment) {
                $attachment['task_id'] = $task->id;
                $attachment['media_id'] = $attachment['media_id'];
                $attachment['title'] = $attachment['title'];
                $attachment['creator_id']= $user->id;
                $attachment['account_id']= $user->account_id;
                $attachment = $this->AttachmentRepository->create($attachment);
            }
        }

        $emailLink = '';
        if (isset($input['link']) && !empty($input['link'])) {
            $emailLink = $input['link'] . '/tasks/all';
        }

        $this->taskService->notifyAssigneeWithNewTask($task, $emailLink);
        if ($request->todo_id !== null) {
            $progress = $this->todoService->calculateProgress($request->todo_id);
        }

        return $this->sendResponse($task->toArray(), 'Task updated successfully');
    }

    /**
     * Finish the specified Task.
     * GET /tasks-finish/{id}
     * @param int $id
     * @return Response
     * @urlParam id required The ID of the Attachment.
     */
    public function finishTask($id)
    {
        $user = Auth::user();

        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            return $this->sendError('Task not found');
        }

        $hasAccess = $this->taskService->hasUpdateAccess($user->id, $task->creator_id, Permission::TASK_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }
        $input['status'] = TASK::STATUS_FINISHED;
        $task = $this->taskRepository->update($input, $id);
        // log task update status

        $taskLog = array('task_id' => $id, 'assignee_id' => $user->id, 'task_status' => TASK::STATUS_FINISHED, 'status' => TaskLog::UPDATE_STATUS);
        TaskLog::create($taskLog);

        $this->taskService->notifyFinishTask($task);

        return $this->sendResponse($task->toArray(), 'Task updated successfully');
    }

    /**
     * Delete Task Details
     * Delete /tasks/{id}
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $task = $this->taskRepository->find($id);
        $user = Auth::user();
        if (empty($task)) {
            return $this->sendError('Task not found');
        }

        $hasAccess = $this->taskService->hasReadAccess($user->id, $task->creator_id, Permission::TASK_CODE);
        if (!$hasAccess) {
            return $this->forbiddenResponse();
        }
        if ($task->todo_id !== null) {
            $progress = $this->todoService->calculateProgress($task->todo_id);
        }

        $task->delete();

        return $this->sendResponse($task->toArray(), 'Task deleted successfully');
    }
}
